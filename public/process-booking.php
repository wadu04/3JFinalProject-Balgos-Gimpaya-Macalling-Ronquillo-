<?php
session_start();
require_once '../app/config/database.php';

header('Content-Type: application/json');
$response = array();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response['status'] = 'error';
    $response['message'] = 'Please log in to book an appointment';
    $response['redirect'] = 'login.php';
    echo json_encode($response);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $conn = $database->getConnection();

    $user_id = $_SESSION['user_id'];
    $service_id = filter_input(INPUT_POST, 'service_id', FILTER_SANITIZE_NUMBER_INT);
    $therapist_id = filter_input(INPUT_POST, 'therapist_id', FILTER_SANITIZE_NUMBER_INT);
    $selected_datetime = filter_input(INPUT_POST, 'selected_datetime', FILTER_SANITIZE_STRING);
    $payment_method = filter_input(INPUT_POST, 'payment_method', FILTER_SANITIZE_STRING);

    try {
        // Start transaction
        $conn->beginTransaction();

        // Get service duration
        $stmt = $conn->prepare("SELECT duration, price FROM services WHERE id = ?");
        $stmt->bind_param("i", $service_id);
        $stmt->execute();
        $service = $stmt->get_result()->fetch_assoc();

        // Calculate end time
        $start_time = new DateTime($selected_datetime);
        $end_time = clone $start_time;
        $end_time->add(new DateInterval('PT' . $service['duration'] . 'M'));

        // Check therapist availability
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count 
            FROM appointments 
            WHERE therapist_id = ? 
            AND status != 'canceled'
            AND (
                (start_time <= ? AND end_time > ?) OR
                (start_time < ? AND end_time >= ?) OR
                (start_time >= ? AND end_time <= ?)
            )
        ");
        $start_str = $start_time->format('Y-m-d H:i:s');
        $end_str = $end_time->format('Y-m-d H:i:s');
        $stmt->bind_param("issssss", $therapist_id, $end_str, $start_str, $end_str, $start_str, $start_str, $end_str);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if ($result['count'] > 0) {
            throw new Exception("Selected time slot is not available");
        }

        // Create appointment
        $stmt = $conn->prepare("
            INSERT INTO appointments (user_id, therapist_id, service_id, start_time, end_time, status)
            VALUES (?, ?, ?, ?, ?, 'confirmed')
        ");
        $stmt->bind_param("iiiss", $user_id, $therapist_id, $service_id, $start_str, $end_str);
        $stmt->execute();
        $appointment_id = $conn->insert_id;

        // Create payment record
        $stmt = $conn->prepare("
            INSERT INTO payments (appointment_id, payment_method, payment_status, amount)
            VALUES (?, ?, 'unpaid', ?)
        ");
        $stmt->bind_param("isd", $appointment_id, $payment_method, $service['price']);
        $stmt->execute();

        // Commit transaction
        $conn->commit();

        $response['status'] = 'success';
        $response['message'] = 'Appointment booked successfully';
        $response['redirect'] = 'my-appointments.php';

    } catch (Exception $e) {
        $conn->rollBack();
        $response['status'] = 'error';
        $response['message'] = $e->getMessage();
    }

    echo json_encode($response);
    exit();
}

$response['status'] = 'error';
$response['message'] = 'Invalid request method';
echo json_encode($response);
