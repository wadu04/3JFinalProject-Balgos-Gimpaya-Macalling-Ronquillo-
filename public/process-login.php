<?php
session_start();
require_once '../app/config/database.php';

header('Content-Type: application/json');
$response = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();

    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        try {
            $query = "SELECT id, username, password, role FROM users WHERE username = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                $response['status'] = 'success';
                $response['message'] = 'Login successful!';
                $response['user'] = [
                    'username' => $user['username'],
                    'role' => $user['role']
                ];
            } else {
                $response['status'] = 'error';
                $response['message'] = 'Invalid username or password';
            }
        } catch (PDOException $e) {
            $response['status'] = 'error';
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
    } else {
        $response['status'] = 'error';
        $response['message'] = 'Please fill in all fields';
    }
} else {
    $response['status'] = 'error';
    $response['message'] = 'Invalid request method';
}

echo json_encode($response);
