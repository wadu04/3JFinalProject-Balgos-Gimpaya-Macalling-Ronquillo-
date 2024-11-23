<?php
session_start();
require_once '../app/config/database.php';

header('Content-Type: application/json');
$response = array();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response['status'] = 'error';
    $response['message'] = 'Please log in to book tickets';
    $response['redirect'] = 'login-form.php';
    echo json_encode($response);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();

    $user_id = $_SESSION['user_id'];
    $bus_class_id = filter_input(INPUT_POST, 'busClass', FILTER_SANITIZE_NUMBER_INT);
    $schedule = filter_input(INPUT_POST, 'schedule', FILTER_SANITIZE_STRING);
    $selected_seats = filter_input(INPUT_POST, 'selectedSeats', FILTER_SANITIZE_STRING);
    $payment_method = filter_input(INPUT_POST, 'payment', FILTER_SANITIZE_STRING);

    // Convert selected seats string to array
    $seat_numbers = explode(',', $selected_seats);
    $number_of_seats = count($seat_numbers);

    try {
        // Start transaction
        $db->beginTransaction();

        // Get schedule ID and check seat availability
        $schedule_query = "SELECT id, available_seats FROM schedules 
                          WHERE bus_class_id = ? AND departure_time = ?";
        $schedule_stmt = $db->prepare($schedule_query);
        $schedule_stmt->execute([$bus_class_id, $schedule]);
        $schedule_data = $schedule_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$schedule_data || $schedule_data['available_seats'] < $number_of_seats) {
            $response['status'] = 'error';
            $response['message'] = 'Redirecting to home page...';
            $response['redirect'] = 'index.php';
            echo json_encode($response);
            exit();
        }

        // Check if any of the selected seats are already booked
        $check_seats_query = "SELECT bs.seat_number 
                            FROM booked_seats bs
                            JOIN bookings b ON bs.booking_id = b.id
                            WHERE b.schedule_id = ? AND bs.seat_number IN (" . str_repeat('?,', count($seat_numbers) - 1) . "?)";
        $check_seats_params = array_merge([$schedule_data['id']], $seat_numbers);
        $check_seats_stmt = $db->prepare($check_seats_query);
        $check_seats_stmt->execute($check_seats_params);
        
        if ($check_seats_stmt->rowCount() > 0) {
            throw new Exception('Some selected seats are already booked');
        }

        // Calculate total amount
        $price_query = "SELECT price FROM bus_classes WHERE id = ?";
        $price_stmt = $db->prepare($price_query);
        $price_stmt->execute([$bus_class_id]);
        $price_data = $price_stmt->fetch(PDO::FETCH_ASSOC);
        $total_amount = $price_data['price'] * $number_of_seats;

        // Create booking
        $booking_query = "INSERT INTO bookings (user_id, schedule_id, bus_class_id, 
                         number_of_seats, total_amount, payment_method) 
                         VALUES (?, ?, ?, ?, ?, ?)";
        $booking_stmt = $db->prepare($booking_query);
        $booking_stmt->execute([
            $user_id,
            $schedule_data['id'],
            $bus_class_id,
            $number_of_seats,
            $total_amount,
            $payment_method
        ]);
        $booking_id = $db->lastInsertId();

        // Insert booked seats
        $insert_seats_query = "INSERT INTO booked_seats (booking_id, seat_number) VALUES (?, ?)";
        $insert_seats_stmt = $db->prepare($insert_seats_query);
        foreach ($seat_numbers as $seat_number) {
            $insert_seats_stmt->execute([$booking_id, $seat_number]);
        }

        // Update available seats
        $update_seats = "UPDATE schedules 
                        SET available_seats = available_seats - ? 
                        WHERE id = ?";
        $update_stmt = $db->prepare($update_seats);
        $update_stmt->execute([$number_of_seats, $schedule_data['id']]);

        // Commit transaction
        $db->commit();

        $response['status'] = 'success';
        $response['message'] = 'Booking successful!';
        $response['booking_id'] = $booking_id;

    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollBack();
        $response['status'] = 'error';
        $response['message'] = 'Redirecting to home page...';
        $response['redirect'] = 'index.php';
    }
} else {
    $response['status'] = 'error';
    $response['message'] = 'Invalid request method';
    $response['redirect'] = 'index.php';
}

echo json_encode($response);
