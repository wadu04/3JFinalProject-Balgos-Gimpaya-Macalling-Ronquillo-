<?php
session_start();
require_once '../app/config/database.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login-form.php');
    exit();
}

if (isset($_GET['id'])) {
    $database = new Database();
    $db = $database->getConnection();

    try {
        // Start transaction
        $db->beginTransaction();

        // Get booking details first
        $booking_query = "SELECT b.*, u.email, u.fullname, bc.class_name, s.departure_time 
                         FROM bookings b
                         JOIN users u ON b.user_id = u.id
                         JOIN bus_classes bc ON b.bus_class_id = bc.id
                         JOIN schedules s ON b.schedule_id = s.id
                         WHERE b.id = ?";
        $booking_stmt = $db->prepare($booking_query);
        $booking_stmt->execute([$_GET['id']]);
        $booking = $booking_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$booking) {
            $_SESSION['error'] = "Booking not found";
            header('Location: dashboard.php');
            exit();
        }

        // Only allow confirmation if booking is pending
        if ($booking['payment_status'] === 'pending') {
            // Update booking status to confirmed
            $query = "UPDATE bookings SET payment_status = 'confirmed' WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$_GET['id']]);

            // Commit transaction
            $db->commit();
            $_SESSION['message'] = "Booking confirmed successfully";
        } else {
            $_SESSION['error'] = "Booking cannot be confirmed - current status: " . $booking['payment_status'];
        }
    } catch (PDOException $e) {
        // Rollback transaction on error
        $db->rollBack();
        $_SESSION['error'] = "Error confirming booking: " . $e->getMessage();
    }
}

header('Location: dashboard.php');
exit();
?>
