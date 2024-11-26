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

// Handle appointment status updates
if (isset($_POST['update_status'])) {
    $appointment_id = filter_input(INPUT_POST, 'appointment_id', FILTER_VALIDATE_INT);
    $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);
    
    if ($appointment_id && in_array($status, ['pending', 'confirmed', 'completed', 'cancelled'])) {
        $stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $appointment_id);
        
        if ($stmt->execute()) {
            // If appointment is confirmed, create a payment record if it doesn't exist
            if ($status === 'confirmed') {
                $stmt = $conn->prepare("
                    INSERT INTO payments (appointment_id, amount, payment_status)
                    SELECT a.id, s.price, 'unpaid'
                    FROM appointments a
                    JOIN services s ON a.service_id = s.id
                    WHERE a.id = ?
                    AND NOT EXISTS (SELECT 1 FROM payments WHERE appointment_id = ?)
                ");
                $stmt->bind_param("ii", $appointment_id, $appointment_id);
                $stmt->execute();
            }
            
            $_SESSION['success_message'] = "Appointment status updated successfully.";
        } else {
            $_SESSION['error_message'] = "Error updating appointment status.";
        }
        header('Location: appointments.php');
        exit();
    }
}

// Fetch all appointments with related information
$stmt = $conn->prepare("
    SELECT 
        a.*,
        s.service_name,
        s.price,
        u.fullname as client_name,
        u.email as client_email,
        t.fullname as therapist_name,
        p.payment_status,
        p.payment_method
    FROM appointments a
    JOIN services s ON a.service_id = s.id
    JOIN users u ON a.user_id = u.id
    JOIN users t ON a.therapist_id = t.id
    LEFT JOIN payments p ON a.id = p.appointment_id
    ORDER BY a.start_time DESC
");
$stmt->execute();
$appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Management - Serenity Spa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
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
        .sidebar .nav-link:hover {
            background-color: #f8f9fa;
        }
        .appointment-details {
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h5>Admin Panel</h5>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="users.php">
                                <i class="fas fa-users me-2"></i>
                                Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="services.php">
                                <i class="fas fa-spa me-2"></i>
                                Services
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="appointments.php">
                                <i class="fas fa-calendar-alt me-2"></i>
                                Appointments
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>
                                Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Appointment Management</h1>
                </div>

                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['success_message'];
                        unset($_SESSION['success_message']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['error_message'];
                        unset($_SESSION['error_message']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="appointmentsTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Client</th>
                                        <th>Service</th>
                                        <th>Therapist</th>
                                        <th>Date & Time</th>
                                        <th>Status</th>
                                        <th>Payment</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($appointments as $appointment): ?>
                                        <tr>
                                            <td><?php echo $appointment['id']; ?></td>
                                            <td>
                                                <div><?php echo htmlspecialchars($appointment['client_name']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($appointment['client_email']); ?></small>
                                            </td>
                                            <td>
                                                <div><?php echo htmlspecialchars($appointment['service_name']); ?></div>
                                                <small class="text-muted">₱<?php echo number_format($appointment['price'], 2); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($appointment['therapist_name']); ?></td>
                                            <td>
                                                <div><?php echo date('F j, Y', strtotime($appointment['start_time'])); ?></div>
                                                <small class="text-muted"><?php echo date('g:i A', strtotime($appointment['start_time'])); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $appointment['status'] === 'confirmed' ? 'success' : 
                                                        ($appointment['status'] === 'pending' ? 'warning' : 
                                                        ($appointment['status'] === 'completed' ? 'info' : 'danger')); 
                                                ?>">
                                                    <?php echo ucfirst($appointment['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($appointment['payment_status']): ?>
                                                    <span class="badge bg-<?php 
                                                        echo $appointment['payment_status'] === 'paid' ? 'success' : 'warning'; 
                                                    ?>">
                                                        <?php echo ucfirst($appointment['payment_status']); ?>
                                                    </span>
                                                    <?php if ($appointment['payment_method']): ?>
                                                        <div class="small text-muted">
                                                            via <?php echo ucfirst($appointment['payment_method']); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">No Payment</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button type="button" 
                                                        class="btn btn-sm btn-primary"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#statusModal<?php echo $appointment['id']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </td>
                                        </tr>

                                        <!-- Status Update Modal -->
                                        <div class="modal fade" id="statusModal<?php echo $appointment['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Update Appointment Status</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="POST">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                            
                                                            <div class="appointment-details mb-4">
                                                                <h6>Appointment Details</h6>
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <p class="mb-1">
                                                                            <strong>Client:</strong><br>
                                                                            <?php echo htmlspecialchars($appointment['client_name']); ?>
                                                                        </p>
                                                                        <p class="mb-1">
                                                                            <strong>Service:</strong><br>
                                                                            <?php echo htmlspecialchars($appointment['service_name']); ?>
                                                                        </p>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <p class="mb-1">
                                                                            <strong>Date:</strong><br>
                                                                            <?php echo date('F j, Y', strtotime($appointment['start_time'])); ?>
                                                                        </p>
                                                                        <p class="mb-1">
                                                                            <strong>Time:</strong><br>
                                                                            <?php echo date('g:i A', strtotime($appointment['start_time'])); ?>
                                                                        </p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Update Status</label>
                                                                <select class="form-select" name="status" required>
                                                                    <option value="pending" <?php echo $appointment['status'] === 'pending' ? 'selected' : ''; ?>>
                                                                        Pending
                                                                    </option>
                                                                    <option value="confirmed" <?php echo $appointment['status'] === 'confirmed' ? 'selected' : ''; ?>>
                                                                        Confirmed
                                                                    </option>
                                                                    <option value="completed" <?php echo $appointment['status'] === 'completed' ? 'selected' : ''; ?>>
                                                                        Completed
                                                                    </option>
                                                                    <option value="cancelled" <?php echo $appointment['status'] === 'cancelled' ? 'selected' : ''; ?>>
                                                                        Cancelled
                                                                    </option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#appointmentsTable').DataTable({
                order: [[4, 'desc']], // Sort by date column by default
                pageLength: 10,
                language: {
                    search: "Search appointments:"
                }
            });
        });
    </script>
</body>
</html>
