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

$user = null;
$message = '';
$error = '';

// Fetch user data if ID is provided
if (isset($_GET['id'])) {
    try {
        $query = "SELECT * FROM users WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$_GET['id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "Error fetching user: " . $e->getMessage();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id = $_POST['id'];
        $username = $_POST['username'];
        $fullname = $_POST['fullname'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $role = $_POST['role'];

        // Update user information
        $query = "UPDATE users SET username = ?, fullname = ?, email = ?, phone = ?, role = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$username, $fullname, $email, $phone, $role, $id]);

        // Update password if provided
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $query = "UPDATE users SET password = ? WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$password, $id]);
        }

        $_SESSION['message'] = "User updated successfully";
        header('Location: dashboard.php');
        exit();
    } catch (PDOException $e) {
        $error = "Error updating user: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
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
        <h2>Edit User</h2>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($user): ?>
            <form method="POST" action="edit-user.php">
                <input type="hidden" name="id" value="<?= htmlspecialchars($user['id']) ?>">
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="fullname">Full Name</label>
                    <input type="text" id="fullname" name="fullname" value="<?= htmlspecialchars($user['fullname']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="role">Role</label>
                    <select id="role" name="role" required>
                        <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
                        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="password">New Password (leave blank to keep current)</label>
                    <input type="password" id="password" name="password">
                </div>

                <div class="btn-container">
                    <button type="submit" class="btn btn-primary">Update User</button>
                    <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        <?php else: ?>
            <p>User not found.</p>
            <div class="btn-container">
                <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
