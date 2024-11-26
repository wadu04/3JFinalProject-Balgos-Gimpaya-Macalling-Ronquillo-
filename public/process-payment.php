<?php
require_once '../app/config/database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$appointment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch appointment details
$stmt = $conn->prepare("
    SELECT 
        a.*,
        s.service_name,
        s.price,
        p.payment_method,
        p.payment_status
    FROM appointments a
    JOIN services s ON a.service_id = s.id
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
    
    try {
        $conn->begin_transaction();

        // Update payment record
        $stmt = $conn->prepare("
            UPDATE payments 
            SET payment_status = 'paid',
                payment_method = ?,
                transaction_id = ?,
                payment_date = CURRENT_TIMESTAMP
            WHERE appointment_id = ?
        ");
        $transaction_id = uniqid('PAY-', true);
        $stmt->bind_param("ssi", $payment_method, $transaction_id, $appointment_id);
        $stmt->execute();

        $conn->commit();
        
        $_SESSION['success_message'] = "Payment processed successfully!";
        header('Location: my-appointments.php');
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $error = "Payment processing failed. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Process Payment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body">
                        <h2 class="card-title text-center mb-4">Process Payment</h2>

                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>

                        <div class="mb-4">
                            <h5>Appointment Details</h5>
                            <ul class="list-unstyled">
                                <li>Service: <?php echo htmlspecialchars($appointment['service_name']); ?></li>
                                <li>Date: <?php echo date('F j, Y', strtotime($appointment['start_time'])); ?></li>
                                <li>Time: <?php echo date('g:i A', strtotime($appointment['start_time'])); ?></li>
                                <li>Amount: ₱<?php echo number_format($appointment['price'], 2); ?></li>
                            </ul>
                        </div>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label">Payment Method</label>
                                <select class="form-select" name="payment_method" required>
                                    <option value="cash" <?php echo $appointment['payment_method'] === 'cash' ? 'selected' : ''; ?>>Cash</option>
                                    <option value="credit_card" <?php echo $appointment['payment_method'] === 'credit_card' ? 'selected' : ''; ?>>Credit Card</option>
                                    <option value="paypal" <?php echo $appointment['payment_method'] === 'paypal' ? 'selected' : ''; ?>>PayPal</option>
                                </select>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Process Payment</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
