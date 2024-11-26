<?php
require_once '../app/config/database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();
$appointment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    $conn->begin_transaction();

    // Verify appointment belongs to user and is cancellable
    $stmt = $conn->prepare("
        SELECT id, status 
        FROM appointments 
        WHERE id = ? AND user_id = ? AND (status = 'pending' OR status = 'confirmed')
    ");
    $stmt->bind_param("ii", $appointment_id, $_SESSION['user_id']);
    $stmt->execute();
    $appointment = $stmt->get_result()->fetch_assoc();

    if (!$appointment) {
        throw new Exception("Invalid appointment or cannot be cancelled");
    }

    // Update appointment status
    $stmt = $conn->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ?");
    $stmt->bind_param("i", $appointment_id);
    $stmt->execute();

    // Update payment status if exists
    $stmt = $conn->prepare("UPDATE payments SET payment_status = 'cancelled' WHERE appointment_id = ?");
    $stmt->bind_param("i", $appointment_id);
    $stmt->execute();

    $conn->commit();
    $_SESSION['success_message'] = "Appointment cancelled successfully";

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_message'] = $e->getMessage();
}

header('Location: appointments.php');
exit();
