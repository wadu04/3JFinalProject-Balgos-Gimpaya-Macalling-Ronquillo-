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

        // Delete related bookings first
        $delete_bookings = "DELETE FROM bookings WHERE user_id = ?";
        $stmt = $db->prepare($delete_bookings);
        $stmt->execute([$_GET['id']]);

        // Delete user
        $delete_user = "DELETE FROM users WHERE id = ?";
        $stmt = $db->prepare($delete_user);
        $stmt->execute([$_GET['id']]);

        // Commit transaction
        $db->commit();

        $_SESSION['message'] = "User deleted successfully";
    } catch (PDOException $e) {
        // Rollback transaction on error
        $db->rollBack();
        $_SESSION['error'] = "Error deleting user: " . $e->getMessage();
    }
}

header('Location: dashboard.php');
exit();