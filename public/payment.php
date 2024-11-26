<?php
session_start();
require_once '../app/config/database.php';

// Create database connection
$database = new Database();
$conn = $database->getConnection();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$appointment_id = filter_input(INPUT_GET, 'appointment_id', FILTER_VALIDATE_INT);

if (!$appointment_id) {
    header('Location: my-appointments.php');
    exit();
}

// Fetch appointment details
$stmt = $conn->prepare("
    SELECT a.*, s.service_name, s.price, s.duration, u.fullname as therapist_name, p.payment_status, p.id as payment_id
    FROM appointments a
    JOIN services s ON a.service_id = s.id
    JOIN users u ON a.therapist_id = u.id
    LEFT JOIN payments p ON a.id = p.appointment_id
    WHERE a.id = ? AND a.user_id = ?
");
$stmt->bind_param("ii", $appointment_id, $_SESSION['user_id']);
$stmt->execute();
$appointment = $stmt->get_result()->fetch_assoc();

if (!$appointment) {
    header('Location: my-appointments.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = filter_input(INPUT_POST, 'payment_method', FILTER_SANITIZE_STRING);
    
    if (in_array($payment_method, ['cash', 'credit_card', 'gcash', 'paypal'])) {
        // Generate unique transaction ID
        $transaction_id = 'TXN' . date('YmdHis') . rand(1000, 9999);
        
        if ($appointment['payment_id']) {
            // Update existing payment record
            $stmt = $conn->prepare("
                UPDATE payments 
                SET payment_method = ?, 
                    payment_status = 'paid',
                    transaction_id = ?,
                    payment_date = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->bind_param("ssi", $payment_method, $transaction_id, $appointment['payment_id']);
        } else {
            // Create new payment record
            $stmt = $conn->prepare("
                INSERT INTO payments (appointment_id, amount, payment_method, payment_status, transaction_id, payment_date)
                VALUES (?, ?, ?, 'paid', ?, CURRENT_TIMESTAMP)
            ");
            $stmt->bind_param("idss", $appointment_id, $appointment['price'], $payment_method, $transaction_id);
        }
        
        if ($stmt->execute()) {
            // Update appointment status
            $stmt = $conn->prepare("UPDATE appointments SET status = 'confirmed' WHERE id = ?");
            $stmt->bind_param("i", $appointment_id);
            $stmt->execute();
            
            $_SESSION['success_message'] = "Payment processed successfully! Your appointment has been confirmed.";
            header('Location: my-appointments.php');
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - Serenity Spa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .payment-option {
            border: 2px solid #dee2e6;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .payment-option:hover {
            border-color: #0d6efd;
            background-color: #f8f9fa;
        }
        .payment-option.selected {
            border-color: #0d6efd;
            background-color: #e7f1ff;
        }
        .payment-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="text-center mb-4">Complete Your Payment</h2>
                        
                        <!-- Appointment Summary -->
                        <div class="bg-light p-4 rounded mb-4">
                            <h5 class="mb-3">Appointment Details</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <ul class="list-unstyled mb-0">
                                        <li class="mb-2"><i class="fas fa-spa me-2 text-primary"></i> 
                                            <strong>Service:</strong> <?php echo htmlspecialchars($appointment['service_name']); ?>
                                        </li>
                                        <li class="mb-2"><i class="fas fa-user me-2 text-primary"></i>
                                            <strong>Therapist:</strong> <?php echo htmlspecialchars($appointment['therapist_name']); ?>
                                        </li>
                                        <li class="mb-2"><i class="fas fa-clock me-2 text-primary"></i>
                                            <strong>Duration:</strong> <?php echo $appointment['duration']; ?> minutes
                                        </li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <ul class="list-unstyled mb-0">
                                        <li class="mb-2"><i class="fas fa-calendar me-2 text-primary"></i>
                                            <strong>Date:</strong> <?php echo date('F j, Y', strtotime($appointment['start_time'])); ?>
                                        </li>
                                        <li class="mb-2"><i class="fas fa-clock me-2 text-primary"></i>
                                            <strong>Time:</strong> <?php echo date('g:i A', strtotime($appointment['start_time'])); ?>
                                        </li>
                                        <li class="mb-2"><i class="fas fa-tag me-2 text-primary"></i>
                                            <strong>Amount:</strong> ₱<?php echo number_format($appointment['price'], 2); ?>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Method Selection -->
                        <form method="POST" id="paymentForm">
                            <h5 class="mb-3">Select Payment Method</h5>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="payment-option" data-method="cash">
                                        <div class="text-center">
                                            <i class="fas fa-money-bill payment-icon text-success"></i>
                                            <h6 class="mb-0">Cash</h6>
                                            <small class="text-muted">Pay at the spa</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <div class="payment-option" data-method="credit_card">
                                        <div class="text-center">
                                            <i class="fas fa-credit-card payment-icon text-primary"></i>
                                            <h6 class="mb-0">Credit Card</h6>
                                            <small class="text-muted">Visa, Mastercard, etc.</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <div class="payment-option" data-method="gcash">
                                        <div class="text-center">
                                            <i class="fas fa-mobile-alt payment-icon text-info"></i>
                                            <h6 class="mb-0">GCash</h6>
                                            <small class="text-muted">Pay via GCash</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <div class="payment-option" data-method="paypal">
                                        <div class="text-center">
                                            <i class="fab fa-paypal payment-icon text-primary"></i>
                                            <h6 class="mb-0">PayPal</h6>
                                            <small class="text-muted">Pay via PayPal</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <input type="hidden" name="payment_method" id="payment_method" required>
                            
                            <div class="d-grid gap-2 mt-4">
                                <button type="submit" class="btn btn-primary btn-lg" id="payButton" disabled>
                                    Complete Payment
                                </button>
                                <a href="my-appointments.php" class="btn btn-outline-secondary">
                                    Back to My Appointments
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const paymentOptions = document.querySelectorAll('.payment-option');
            const paymentMethodInput = document.getElementById('payment_method');
            const payButton = document.getElementById('payButton');
            
            paymentOptions.forEach(option => {
                option.addEventListener('click', function() {
                    // Remove selected class from all options
                    paymentOptions.forEach(opt => opt.classList.remove('selected'));
                    
                    // Add selected class to clicked option
                    this.classList.add('selected');
                    
                    // Update hidden input and enable pay button
                    paymentMethodInput.value = this.dataset.method;
                    payButton.disabled = false;
                });
            });
        });
    </script>
</body>
</html>
