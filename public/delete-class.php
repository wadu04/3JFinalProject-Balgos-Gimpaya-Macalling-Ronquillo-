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

        // Delete related schedules first
        $delete_schedules = "DELETE FROM schedules WHERE bus_class_id = ?";
        $stmt = $db->prepare($delete_schedules);
        $stmt->execute([$_GET['id']]);

        // Delete bus class
        $delete_class = "DELETE FROM bus_classes WHERE id = ?";
        $stmt = $db->prepare($delete_class);
        $stmt->execute([$_GET['id']]);

        // Commit transaction
        $db->commit();

        $_SESSION['message'] = "Bus class deleted successfully";
    } catch (PDOException $e) {
        // Rollback transaction on error
        $db->rollBack();
        $_SESSION['error'] = "Error deleting bus class: " . $e->getMessage();
    }
}

header('Location: dashboard.php');
exit();
