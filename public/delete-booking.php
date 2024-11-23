<?php
session_start();
require_once '../app/config/database.php';

// Check if user is logged in and is admin
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

        // Delete booked seats first
        $delete_seats = "DELETE FROM booked_seats WHERE booking_id = ?";
        $stmt = $db->prepare($delete_seats);
        $stmt->execute([$_GET['id']]);

        // Delete booking
        $delete_booking = "DELETE FROM bookings WHERE id = ?";
        $stmt = $db->prepare($delete_booking);
        $stmt->execute([$_GET['id']]);

        // Commit transaction
        $db->commit();

        $_SESSION['message'] = "Booking deleted successfully";
    } catch (PDOException $e) {
        // Rollback transaction on error
        $db->rollBack();
        $_SESSION['error'] = "Error deleting booking: " . $e->getMessage();
    }
}

header('Location: dashboard.php');
exit();
