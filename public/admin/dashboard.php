<?php
session_start();
require_once '../../app/config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Get counts for dashboard
$counts = [];

// Users count
$stmt = $conn->query("SELECT COUNT(*) as count FROM users WHERE role != 'admin'");
$counts['users'] = $stmt->fetch_assoc()['count'];

// Services count
$stmt = $conn->query("SELECT COUNT(*) as count FROM services");
$counts['services'] = $stmt->fetch_assoc()['count'];

// Appointments count
$stmt = $conn->query("SELECT COUNT(*) as count FROM appointments");
$counts['appointments'] = $stmt->fetch_assoc()['count'];

// Pending payments count
$stmt = $conn->query("SELECT COUNT(*) as count FROM payments WHERE payment_status = 'unpaid'");
$counts['pending_payments'] = $stmt->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Serenity Spa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar {
            min-height: 100vh;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
        }
        .sidebar .nav-link {
            color: #333;
            padding: 1rem;
        }
        .sidebar .nav-link.active {
            background-color: #e9ecef;
        }
        .dashboard-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'admin-navbar.php'; ?>
            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                </div>

                <!-- Dashboard Cards -->
                <div class="row">
                    <div class="col-md-6 col-xl-3 mb-4">
                        <div class="card dashboard-card text-white bg-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-uppercase mb-1">Users</h6>
                                        <h2 class="mb-0"><?php echo $counts['users']; ?></h2>
                                    </div>
                                    <div class="square-icon">
                                        <i class="fas fa-users fa-2x opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent border-0">
                                <a href="users.php" class="text-white text-decoration-none small">
                                    View Details <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-xl-3 mb-4">
                        <div class="card dashboard-card text-white bg-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-uppercase mb-1">Services</h6>
                                        <h2 class="mb-0"><?php echo $counts['services']; ?></h2>
                                    </div>
                                    <div class="square-icon">
                                        <i class="fas fa-spa fa-2x opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent border-0">
                                <a href="services.php" class="text-white text-decoration-none small">
                                    View Details <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-xl-3 mb-4">
                        <div class="card dashboard-card text-white bg-info">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-uppercase mb-1">Appointments</h6>
                                        <h2 class="mb-0"><?php echo $counts['appointments']; ?></h2>
                                    </div>
                                    <div class="square-icon">
                                        <i class="fas fa-calendar-alt fa-2x opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent border-0">
                                <a href="appointments.php" class="text-white text-decoration-none small">
                                    View Details <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-xl-3 mb-4">
                        <div class="card dashboard-card text-white bg-warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-uppercase mb-1">Pending Payments</h6>
                                        <h2 class="mb-0"><?php echo $counts['pending_payments']; ?></h2>
                                    </div>
                                    <div class="square-icon">
                                        <i class="fas fa-credit-card fa-2x opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent border-0">
                                <a href="appointments.php" class="text-white text-decoration-none small">
                                    View Details <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Recent Appointments</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Client</th>
                                                <th>Service</th>
                                                <th>Date</th>
                                                <th>Status</th>
                                                <th>Payment</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $stmt = $conn->query("
                                                SELECT 
                                                    a.*, 
                                                    s.service_name,
                                                    u.fullname as client_name,
                                                    p.payment_status
                                                FROM appointments a
                                                JOIN services s ON a.service_id = s.id
                                                JOIN users u ON a.user_id = u.id
                                                LEFT JOIN payments p ON a.id = p.appointment_id
                                                ORDER BY a.start_time DESC
                                                LIMIT 5
                                            ");
                                            while ($row = $stmt->fetch_assoc()):
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['client_name']); ?></td>
                                                <td><?php echo htmlspecialchars($row['service_name']); ?></td>
                                                <td><?php echo date('M j, Y g:i A', strtotime($row['start_time'])); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $row['status'] === 'confirmed' ? 'success' : 
                                                            ($row['status'] === 'pending' ? 'warning' : 
                                                            ($row['status'] === 'completed' ? 'info' : 'danger')); 
                                                    ?>">
                                                        <?php echo ucfirst($row['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $row['payment_status'] === 'paid' ? 'success' : 'warning'; 
                                                    ?>">
                                                        <?php echo ucfirst($row['payment_status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
