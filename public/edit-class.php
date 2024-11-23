<?php
session_start();
require_once '../app/config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login-form.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$bus_class = null;
$message = '';
$error = '';

// Fetch bus class data if ID is provided
if (isset($_GET['id'])) {
    try {
        $query = "SELECT * FROM bus_classes WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$_GET['id']]);
        $bus_class = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "Error fetching bus class: " . $e->getMessage();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id = $_POST['id'];
        $class_name = $_POST['class_name'];
        $price = $_POST['price'];
        $total_seats = $_POST['total_seats'];

        // Update bus class information
        $query = "UPDATE bus_classes SET class_name = ?, price = ?, total_seats = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$class_name, $price, $total_seats, $id]);

        $_SESSION['message'] = "Bus class updated successfully";
        header('Location: dashboard.php');
        exit();
    } catch (PDOException $e) {
        $error = "Error updating bus class: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Bus Class</title>
    <link rel="stylesheet" href="css/register-style.css">
    <style>
        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .btn-container {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            color: white;
            text-decoration: none;
            text-align: center;
        }
        .btn-primary {
            background: #4CAF50;
        }
        .btn-secondary {
            background: #666;
        }
        .error {
            color: red;
            margin-bottom: 10px;
        }
        .success {
            color: green;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Edit Bus Class</h2>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($bus_class): ?>
            <form method="POST" action="edit-class.php">
                <input type="hidden" name="id" value="<?= htmlspecialchars($bus_class['id']) ?>">
                
                <div class="form-group">
                    <label for="class_name">Class Name</label>
                    <input type="text" id="class_name" name="class_name" value="<?= htmlspecialchars($bus_class['class_name']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="price">Price</label>
                    <input type="number" id="price" name="price" value="<?= htmlspecialchars($bus_class['price']) ?>" step="0.01" required>
                </div>

                <div class="form-group">
                    <label for="total_seats">Total Seats</label>
                    <input type="number" id="total_seats" name="total_seats" value="<?= htmlspecialchars($bus_class['total_seats']) ?>" required>
                </div>

                <div class="btn-container">
                    <button type="submit" class="btn btn-primary">Update Bus Class</button>
                    <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        <?php else: ?>
            <p>Bus class not found.</p>
            <div class="btn-container">
                <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
