<?php
session_start();
require_once '../app/config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
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
                         WHERE b.id = ? AND (b.user_id = ? OR ? = 'admin')";
        $booking_stmt = $db->prepare($booking_query);
        $booking_stmt->execute([$_GET['id'], $_SESSION['user_id'], $_SESSION['role']]);
        $booking = $booking_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$booking) {
            $_SESSION['error'] = "Booking not found or access denied";
            header('Location: dashboard.php');
            exit();
        }

        // Only allow cancellation if booking is not already cancelled
        if ($booking['payment_status'] !== 'cancelled') {
            // Update booking status to cancelled
            $query = "UPDATE bookings SET payment_status = 'cancelled' WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$_GET['id']]);

            // Add notification
            $notification_query = "INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, NOW())";
            $notification_stmt = $db->prepare($notification_query);
            $message = "Your booking for " . $booking['class_name'] . " bus at " . 
                      date('h:i A', strtotime($booking['departure_time'])) . 
                      " has been cancelled.";
            $notification_stmt->execute([$booking['user_id'], $message]);

            // Commit transaction
            $db->commit();
            $_SESSION['message'] = "Booking cancelled successfully";
        } else {
            $_SESSION['error'] = "Booking is already cancelled";
        }
    } catch (PDOException $e) {
        // Rollback transaction on error
        $db->rollBack();
        $_SESSION['error'] = "Error cancelling booking: " . $e->getMessage();
    }
}

header('Location: dashboard.php');
exit();
?>
