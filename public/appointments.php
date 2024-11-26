<?php
session_start();
require_once '../app/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}


$database = new Database();
$conn = $database->getConnection();


$stmt = $conn->prepare("
    SELECT 
        a.id,
        a.start_time,
        a.end_time,
        a.status,
        s.service_name,
        s.description as service_description,
        s.duration,
        s.price,
        u.fullname as therapist_name,
        p.payment_method,
        p.payment_status,
        p.transaction_id,
        p.payment_date
    FROM appointments a
    JOIN services s ON a.service_id = s.id
    JOIN users u ON a.therapist_id = u.id
    LEFT JOIN payments p ON a.id = p.appointment_id
    WHERE a.user_id = ?
    ORDER BY a.start_time DESC
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get user details
$stmt = $conn->prepare("SELECT fullname FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments - Serenity Spa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .appointment-card {
            transition: transform 0.2s;
            height: 100%;
        }
        .appointment-card:hover {
            transform: translateY(-5px);
        }
        .status-badge {
            position: absolute;
            top: 20px;
            right: 20px;
        }
        .payment-badge {
            position: absolute;
            top: 50px;
            right: 20px;
        }
        .appointment-header {
            background: linear-gradient(45deg, #6a11cb, #2575fc);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 20px 20px;
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>

    <!-- Header Section -->
    <div class="appointment-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">My Appointments</h1>
                    <p class="mb-0">Welcome back, <?php echo htmlspecialchars($user['fullname']); ?>!</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="services.php" class="btn btn-light btn-lg">
                        <i class="fas fa-plus-circle me-2"></i>Book New Appointment
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container mb-5">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['success_message'];
                unset($_SESSION['success_message']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (empty($appointments)): ?>
            <div class="text-center py-5">
                <i class="fas fa-calendar-alt fa-4x text-muted mb-3"></i>
                <h3>No Appointments Yet</h3>
                <p class="text-muted">You haven't made any appointments yet. Ready to relax?</p>
                <a href="services.php" class="btn btn-primary btn-lg">Book Your First Appointment</a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($appointments as $appointment): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card appointment-card shadow-sm">
                            <!-- Status Badge -->
                            <span class="status-badge badge bg-<?php 
                                echo $appointment['status'] === 'confirmed' ? 'success' : 
                                    ($appointment['status'] === 'pending' ? 'warning' : 
                                    ($appointment['status'] === 'completed' ? 'info' : 'danger')); 
                            ?>">
                                <?php echo ucfirst($appointment['status']); ?>
                            </span>
                            
                            <!-- Payment Badge -->
                            <?php if ($appointment['payment_status']): ?>
                                <span class="payment-badge badge bg-<?php 
                                    echo $appointment['payment_status'] === 'paid' ? 'success' : 'warning'; 
                                ?>">
                                    <?php echo ucfirst($appointment['payment_status']); ?>
                                </span>
                            <?php endif; ?>

                            <div class="card-body">
                                <h5 class="card-title mb-3">
                                    <i class="fas fa-spa text-primary me-2"></i>
                                    <?php echo htmlspecialchars($appointment['service_name']); ?>
                                </h5>
                                
                                <div class="mb-3">
                                    <p class="text-muted small mb-2"><?php echo htmlspecialchars($appointment['service_description']); ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-primary">
                                            <i class="fas fa-clock me-1"></i>
                                            <?php echo $appointment['duration']; ?> minutes
                                        </span>
                                        <span class="text-primary">
                                            ₱<?php echo number_format($appointment['price'], 2); ?>
                                        </span>
                                    </div>
                                </div>

                                <hr>

                                <div class="mb-3">
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <small class="text-muted d-block">Date</small>
                                            <strong><?php echo date('F j, Y', strtotime($appointment['start_time'])); ?></strong>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block">Time</small>
                                            <strong><?php echo date('g:i A', strtotime($appointment['start_time'])); ?></strong>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <small class="text-muted d-block">Therapist</small>
                                    <strong><?php echo htmlspecialchars($appointment['therapist_name']); ?></strong>
                                </div>

                                <?php if ($appointment['payment_method']): ?>
                                    <div class="mb-3">
                                        <small class="text-muted d-block">Payment Details</small>
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <strong><?php echo ucfirst(str_replace('_', ' ', $appointment['payment_method'])); ?></strong>
                                            </div>
                                            <div class="col-6">
                                                <strong class="text-<?php echo $appointment['payment_status'] === 'paid' ? 'success' : 'warning'; ?>">
                                                    <?php echo ucfirst($appointment['payment_status']); ?>
                                                </strong>
                                            </div>
                                        </div>
                                        <?php if ($appointment['transaction_id']): ?>
                                            <small class="text-muted d-block mt-1">
                                                Transaction ID: <?php echo htmlspecialchars($appointment['transaction_id']); ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($appointment['status'] === 'pending' || ($appointment['status'] === 'confirmed' && $appointment['payment_status'] === 'unpaid')): ?>
                                    <div class="d-grid gap-2">
                                        <?php if ($appointment['payment_status'] === 'unpaid'): ?>
                                            <a href="payment.php?appointment_id=<?php echo $appointment['id']; ?>" 
                                               class="btn btn-primary">
                                                <i class="fas fa-credit-card me-2"></i>Pay Now
                                            </a>
                                        <?php endif; ?>
                                        
                                        <button type="button" 
                                                class="btn btn-outline-danger"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#cancelModal<?php echo $appointment['id']; ?>">
                                            <i class="fas fa-times-circle me-2"></i>Cancel Appointment
                                        </button>
                                    </div>

                                    <!-- Cancel Modal -->
                                    <div class="modal fade" id="cancelModal<?php echo $appointment['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Cancel Appointment</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Are you sure you want to cancel this appointment?</p>
                                                    <p class="text-muted small">This action cannot be undone.</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Keep It</button>
                                                    <a href="cancel-appointment.php?id=<?php echo $appointment['id']; ?>" 
                                                       class="btn btn-danger">Yes, Cancel It</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
