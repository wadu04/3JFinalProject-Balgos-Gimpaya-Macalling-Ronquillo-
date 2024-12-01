<?php
session_start();
require_once '../../app/config/database.php';

// Create database connection
$database = new Database();
$conn = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_id = filter_input(INPUT_POST, 'service_id', FILTER_VALIDATE_INT);
    $service_name = filter_input(INPUT_POST, 'service_name', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $duration = filter_input(INPUT_POST, 'duration', FILTER_VALIDATE_INT);
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);

    // Validate inputs
    if (!$service_id || !$service_name || !$description || !$duration || !$price) {
        $_SESSION['error'] = "All fields are required and must be valid";
        header("Location: services.php");
        exit();
    }

    // Start with base query
    $query = "UPDATE services SET service_name = ?, description = ?, duration = ?, price = ?";
    $params = array($service_name, $description, $duration, $price);
    $types = "ssid";

    // Handle image upload if new image is provided
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

        // Get old image name
        $stmt = $conn->prepare("SELECT image FROM services WHERE id = ?");
        $stmt->bind_param("i", $service_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $old_image = $result->fetch_assoc()['image'];

        // Generate new filename
        $image_name = strtolower(str_replace(' ', '-', $service_name)) . '.' . $extension;
        $upload_path = "../assets/images/" . $image_name;

        // Move uploaded file
        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
            // Add image to update query
            $query .= ", image = ?";
            $params[] = $image_name;
            $types .= "s";

            // Delete old image if it exists and is different from new image
            if ($old_image && $old_image !== $image_name) {
                $old_image_path = "../assets/images/" . $old_image;
                if (file_exists($old_image_path)) {
                    unlink($old_image_path);
                }
            }
        } else {
            $_SESSION['error'] = "Failed to upload new image";
            header("Location: services.php");
            exit();
        }
    }

    // Complete the query
    $query .= " WHERE id = ?";
    $params[] = $service_id;
    $types .= "i";

    // Prepare and execute the update
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Service updated successfully";
    } else {
        $_SESSION['error'] = "Failed to update service";
    }
} else {
    $_SESSION['error'] = "Invalid request method";
}

header("Location: services.php");
exit();
