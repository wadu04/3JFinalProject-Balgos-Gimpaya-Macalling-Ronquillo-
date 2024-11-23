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

$schedule = null;
$message = '';
$error = '';

// Fetch bus classes for the dropdown
try {
    $classes_query = "SELECT * FROM bus_classes";
    $classes_stmt = $db->prepare($classes_query);
    $classes_stmt->execute();
    $bus_classes = $classes_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching bus classes: " . $e->getMessage();
}

// Fetch schedule data if ID is provided
if (isset($_GET['id'])) {
    try {
        $query = "SELECT * FROM schedules WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$_GET['id']]);
        $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "Error fetching schedule: " . $e->getMessage();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id = $_POST['id'];
        $departure_time = $_POST['departure_time'];
        $bus_class_id = $_POST['bus_class_id'];
        $available_seats = $_POST['available_seats'];

        // Update schedule information
        $query = "UPDATE schedules SET departure_time = ?, bus_class_id = ?, available_seats = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$departure_time, $bus_class_id, $available_seats, $id]);

        $_SESSION['message'] = "Schedule updated successfully";
        header('Location: dashboard.php');
        exit();
    } catch (PDOException $e) {
        $error = "Error updating schedule: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Schedule</title>
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
        .form-group input, .form-group select {
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
        <h2>Edit Schedule</h2>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($schedule): ?>
            <form method="POST" action="edit-schedule.php">
                <input type="hidden" name="id" value="<?= htmlspecialchars($schedule['id']) ?>">
                
                <div class="form-group">
                    <label for="departure_time">Departure Time</label>
                    <input type="time" id="departure_time" name="departure_time" value="<?= htmlspecialchars($schedule['departure_time']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="bus_class_id">Bus Class</label>
                    <select id="bus_class_id" name="bus_class_id" required>
                        <?php foreach ($bus_classes as $class): ?>
                            <option value="<?= $class['id'] ?>" <?= $schedule['bus_class_id'] == $class['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($class['class_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="available_seats">Available Seats</label>
                    <input type="number" id="available_seats" name="available_seats" value="<?= htmlspecialchars($schedule['available_seats']) ?>" required>
                </div>

                <div class="btn-container">
                    <button type="submit" class="btn btn-primary">Update Schedule</button>
                    <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        <?php else: ?>
            <p>Schedule not found.</p>
            <div class="btn-container">
                <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
