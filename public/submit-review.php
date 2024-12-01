<?php
session_start();
require_once '../app/config/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Get POST data
$appointment_id = filter_input(INPUT_POST, 'appointment_id', FILTER_VALIDATE_INT);
$rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
$comment = filter_input(INPUT_POST, 'comment', FILTER_SANITIZE_STRING) ?? '';

// Validate input
if (!$appointment_id || !$rating || $rating < 1 || $rating > 5) {
    http_response_code(400);
    echo json_encode(['error' => 'Please provide a valid rating (1-5 stars)']);
    exit();
}

// Check if appointment exists and belongs to user
$check_query = "SELECT id, status FROM appointments WHERE id = ? AND user_id = ? AND status = 'completed'";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("ii", $appointment_id, $_SESSION['user_id']);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid appointment or not completed']);
    exit();
}

// Check if review already exists
$review_check = "SELECT id FROM reviews WHERE appointment_id = ?";
$check_stmt = $conn->prepare($review_check);
$check_stmt->bind_param("i", $appointment_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows > 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Review already submitted']);
    exit();
}

// Insert review
$insert_query = "INSERT INTO reviews (appointment_id, user_id, rating, comment, created_at) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)";
$insert_stmt = $conn->prepare($insert_query);
$insert_stmt->bind_param("iiis", $appointment_id, $_SESSION['user_id'], $rating, $comment);

if ($insert_stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Thank you for your review!']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to submit review. Please try again.']);
}
