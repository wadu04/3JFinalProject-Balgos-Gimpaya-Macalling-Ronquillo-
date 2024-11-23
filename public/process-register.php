<?php
require_once '../app/config/database.php';

header('Content-Type: application/json');
$response = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();

    // Get and sanitize form data
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $fullname = filter_input(INPUT_POST, 'fullname', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING);

    // Validate inputs
    $errors = array();
    if (empty($username)) $errors[] = "Username is required";
    if (empty($fullname)) $errors[] = "Full name is required";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required";
    if (empty($phone)) $errors[] = "Phone number is required";
    if (empty($password)) $errors[] = "Password is required";
    if (!in_array($role, ['admin', 'user'])) $errors[] = "Invalid role selected";

    if (empty($errors)) {
        try {
            // Check if username or email already exists
            $check_query = "SELECT id FROM users WHERE username = ? OR email = ?";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->execute([$username, $email]);

            if ($check_stmt->rowCount() > 0) {
                $response['status'] = 'error';
                $response['message'] = 'Username or email already exists';
            } else {
                // Insert new user
                $insert_query = "INSERT INTO users (username, fullname, email, phone, password, role) 
                               VALUES (?, ?, ?, ?, ?, ?)";
                $insert_stmt = $db->prepare($insert_query);
                $result = $insert_stmt->execute([
                    $username,
                    $fullname,
                    $email,
                    $phone,
                    $password,
                    $role
                ]);

                if ($result) {
                    $response['status'] = 'success';
                    $response['message'] = 'Registration successful!';
                } else {
                    $response['status'] = 'error';
                    $response['message'] = 'Registration failed';
                }
            }
        } catch (PDOException $e) {
            $response['status'] = 'error';
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
    } else {
        $response['status'] = 'error';
        $response['message'] = 'Validation errors';
        $response['errors'] = $errors;
    }
} else {
    $response['status'] = 'error';
    $response['message'] = 'Invalid request method';
}

echo json_encode($response);
