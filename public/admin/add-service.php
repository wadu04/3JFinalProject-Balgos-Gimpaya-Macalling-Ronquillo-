<?php
session_start();
require_once '../../app/config/database.php';

// Create database connection
$database = new Database();
$conn = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_name = filter_input(INPUT_POST, 'service_name', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $duration = filter_input(INPUT_POST, 'duration', FILTER_VALIDATE_INT);
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);

    // Validate inputs
    if (!$service_name || !$description || !$duration || !$price) {
        $_SESSION['error'] = "All fields are required and must be valid";
        header("Location: services.php");
        exit();
    }

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file_info = pathinfo($_FILES['image']['name']);
        $extension = strtolower($file_info['extension']);
        
        // Validate file type
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($extension, $allowed_types)) {
            $_SESSION['error'] = "Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.";
            header("Location: services.php");
            exit();
        }

        // Generate unique filename
        $image_name = strtolower(str_replace(' ', '-', $service_name)) . '.' . $extension;
        $upload_path = "../assets/images/" . $image_name;

        // Move uploaded file
        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
            // Insert into database
            $stmt = $conn->prepare("INSERT INTO services (service_name, description, duration, price, image) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssiis", $service_name, $description, $duration, $price, $image_name);

            if ($stmt->execute()) {
                $_SESSION['success'] = "Service added successfully";
            } else {
                $_SESSION['error'] = "Failed to add service";
                // Remove uploaded image if database insert fails
                unlink($upload_path);
            }
        } else {
            $_SESSION['error'] = "Failed to upload image";
        }
    } else {
        $_SESSION['error'] = "Image upload failed";
    }
} else {
    $_SESSION['error'] = "Invalid request method";
}

header("Location: services.php");
exit();
