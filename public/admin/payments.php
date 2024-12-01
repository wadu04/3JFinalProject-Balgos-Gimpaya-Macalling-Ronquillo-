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

// Get all payments with related information
$query = "SELECT p.*, a.start_time, u.fullname as customer_name, s.service_name, 
          CONCAT(t.fullname, ' (', t.email, ')') as therapist_info,
          ac.fullname as confirmed_by_name
          FROM payments p
          JOIN appointments a ON p.appointment_id = a.id
          JOIN users u ON a.user_id = u.id
          JOIN services s ON a.service_id = s.id
          JOIN users t ON a.therapist_id = t.id
          LEFT JOIN users ac ON p.confirmed_by = ac.id
          ORDER BY p.payment_date DESC";
$result = $conn->query($query);
$payments = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $payments[] = $row;
    }
}

// Handle payment confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $payment_id = filter_input(INPUT_POST, 'payment_id', FILTER_VALIDATE_INT);
    $action = $_POST['action'];
    $notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING);

    if ($payment_id) {
        if ($action === 'confirm') {
            $update = "UPDATE payments SET 
                      payment_status = 'confirmed',
                      confirmation_date = CURRENT_TIMESTAMP,
                      confirmed_by = ?,
                      notes = ?
                      WHERE id = ?";
            $stmt = $conn->prepare($update);
            $stmt->bind_param("isi", $_SESSION['user_id'], $notes, $payment_id);
            
            if ($stmt->execute()) {
                // Update appointment status to confirmed
                $update_apt = "UPDATE appointments a 
                             JOIN payments p ON a.id = p.appointment_id
                             SET a.status = 'confirmed'
                             WHERE p.id = ?";
                $apt_stmt = $conn->prepare($update_apt);
                $apt_stmt->bind_param("i", $payment_id);
                $apt_stmt->execute();
                
                header("Location: payments.php?success=Payment confirmed successfully");
                exit();
            }
        } elseif ($action === 'cancel') {
            $update = "UPDATE payments SET 
                      payment_status = 'cancelled',
                      notes = ?
                      WHERE id = ?";
            $stmt = $conn->prepare($update);
            $stmt->bind_param("si", $notes, $payment_id);
            
            if ($stmt->execute()) {
                header("Location: payments.php?success=Payment cancelled successfully");
                exit();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Management - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .payment-card {
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .proof-image {
            max-width: 200px;
            height: auto;
        }
    </style>
</head>
<body>
    <?php include 'admin-navbar.php'; ?>

    <div class="container-fluid my-4">
        <h2 class="mb-4">Payment Management</h2>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_GET['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <?php foreach ($payments as $payment): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card payment-card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                Payment #<?php echo $payment['id']; ?>
                                <?php
                                $status_badges = [
                                    'pending' => 'warning',
                                    'confirmed' => 'success',
                                    'cancelled' => 'danger',
                                    'refunded' => 'info'
                                ];
                                $badge_color = $status_badges[$payment['payment_status']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?php echo $badge_color; ?> float-end">
                                    <?php echo ucfirst($payment['payment_status']); ?>
                                </span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <strong>Customer:</strong> <?php echo htmlspecialchars($payment['customer_name']); ?><br>
                                <strong>Service:</strong> <?php echo htmlspecialchars($payment['service_name']); ?><br>
                                <strong>Therapist:</strong> <?php echo htmlspecialchars($payment['therapist_info']); ?><br>
                                <strong>Amount:</strong> ₱<?php echo number_format($payment['amount'], 2); ?><br>
                                <strong>Payment Method:</strong> <?php echo ucfirst($payment['payment_method']); ?><br>
                                <strong>Payment Date:</strong> <?php echo date('F j, Y g:i A', strtotime($payment['payment_date'])); ?>
                            </div>

                            <?php if ($payment['proof_of_payment']): ?>
                                <div class="mb-3">
                                    <strong>Proof of Payment:</strong><br>
                                    <img src="<?php echo htmlspecialchars($payment['proof_of_payment']); ?>" 
                                         class="proof-image" alt="Proof of Payment">
                                </div>
                            <?php endif; ?>

                            <?php if ($payment['payment_status'] === 'confirmed'): ?>
                                <div class="mb-3">
                                    <strong>Confirmed By:</strong> <?php echo htmlspecialchars($payment['confirmed_by_name']); ?><br>
                                    <strong>Confirmation Date:</strong> 
                                    <?php echo date('F j, Y g:i A', strtotime($payment['confirmation_date'])); ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($payment['notes']): ?>
                                <div class="mb-3">
                                    <strong>Notes:</strong><br>
                                    <?php echo nl2br(htmlspecialchars($payment['notes'])); ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($payment['payment_status'] === 'pending'): ?>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-success" 
                                            onclick="openConfirmationModal(<?php echo $payment['id']; ?>, 'confirm')">
                                        Confirm Payment
                                    </button>
                                    <button type="button" class="btn btn-danger" 
                                            onclick="openConfirmationModal(<?php echo $payment['id']; ?>, 'cancel')">
                                        Cancel Payment
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmationModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="payment_id" id="payment_id">
                        <input type="hidden" name="action" id="action">
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes (Optional)</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>

                        <p id="confirmationMessage"></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Confirm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const confirmationModal = new bootstrap.Modal(document.getElementById('confirmationModal'));
        
        function openConfirmationModal(paymentId, action) {
            document.getElementById('payment_id').value = paymentId;
            document.getElementById('action').value = action;
            
            const message = action === 'confirm' 
                ? 'Are you sure you want to confirm this payment?' 
                : 'Are you sure you want to cancel this payment?';
            document.getElementById('confirmationMessage').textContent = message;
            
            confirmationModal.show();
        }
    </script>
</body>
</html>
