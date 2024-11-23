<?php
require_once '../app/config/config.php';
require_once '../app/core/Database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new Database();
    
    // Get form data
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $fullname = filter_input(INPUT_POST, 'fullname', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    $password = $_POST['password'];
    $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING);

    // Validate inputs
    $errors = [];
    if (empty($username)) $errors[] = "Username is required";
    if (empty($fullname)) $errors[] = "Full name is required";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required";
    if (empty($phone)) $errors[] = "Phone number is required";
    if (empty($password)) $errors[] = "Password is required";
    if (!in_array($role, ['admin', 'user'])) $errors[] = "Invalid role selected";

    // Check if username or email already exists
    $checkUser = $db->query("SELECT * FROM users WHERE username = :username OR email = :email", [
        ':username' => $username,
        ':email' => $email
    ]);

    if ($checkUser->rowCount() > 0) {
        $errors[] = "Username or email already exists";
    }

    if (empty($errors)) {
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert user into database
        $query = "INSERT INTO users (username, fullname, email, phone, password, role) 
                 VALUES (:username, :fullname, :email, :phone, :password, :role)";
        
        try {
            $result = $db->query($query, [
                ':username' => $username,
                ':fullname' => $fullname,
                ':email' => $email,
                ':phone' => $phone,
                ':password' => $hashedPassword,
                ':role' => $role
            ]);

            if ($result) {
                $response = [
                    'status' => 'success',
                    'message' => 'Registration successful'
                ];
            } else {
                $response = [
                    'status' => 'error',
                    'message' => 'Registration failed'
                ];
            }
        } catch (PDOException $e) {
            $response = [
                'status' => 'error',
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    } else {
        $response = [
            'status' => 'error',
            'message' => 'Validation errors',
            'errors' => $errors
        ];
    }

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>
