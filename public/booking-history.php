<?php
session_start();
require_once '../app/config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login-form.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Fetch user's bookings
$booking_query = "SELECT b.*, bc.class_name, s.departure_time 
                 FROM bookings b
                 JOIN bus_classes bc ON b.bus_class_id = bc.id
                 JOIN schedules s ON b.schedule_id = s.id
                 WHERE b.user_id = ?
                 ORDER BY b.booking_date DESC";
$booking_stmt = $db->prepare($booking_query);
$booking_stmt->execute([$_SESSION['user_id']]);
$bookings = $booking_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking History</title>
    <link rel="stylesheet" href="css/register-style.css">
    <style>
        .booking-list {
            width: 100%;
            margin-top: 20px;
        }
        .booking-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .booking-ref {
            font-weight: bold;
            color: #333;
        }
        .booking-date {
            color: #666;
            font-size: 0.9em;
        }
        .booking-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        .detail-item {
            display: flex;
            flex-direction: column;
        }
        .detail-label {
            font-size: 0.9em;
            color: #666;
        }
        .detail-value {
            font-weight: 500;
            color: #333;
        }
        .status-pending {
            color: #ff9800;
        }
        .status-completed {
            color: #4caf50;
        }
        .status-cancelled {
            color: #f44336;
        }
        .no-bookings {
            text-align: center;
            padding: 20px;
            color: #666;
        }
        .actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 10px;
            gap: 10px;
        }
        .view-button {
            background: #4CAF50;
            color: white;
            padding: 5px 15px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="registration-form">
            <h2>My Booking History</h2>
            
            <div class="booking-list">
                <?php if (empty($bookings)): ?>
                <div class="no-bookings">
                    <p>You haven't made any bookings yet.</p>
                    <a href="booking-form.php" class="button">Book a Ticket</a>
                </div>
                <?php else: ?>
                    <?php foreach ($bookings as $booking): ?>
                    <div class="booking-card">
                        <div class="booking-header">
                            <span class="booking-ref">Booking #<?php echo str_pad($booking['id'], 6, '0', STR_PAD_LEFT); ?></span>
                            <span class="booking-date"><?php echo date('M d, Y h:i A', strtotime($booking['booking_date'])); ?></span>
                        </div>
                        <div class="booking-details">
                            <div class="detail-item">
                                <span class="detail-label">Route</span>
                                <span class="detail-value">Baguio to Isabela</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Bus Class</span>
                                <span class="detail-value"><?php echo htmlspecialchars($booking['class_name']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Departure</span>
                                <span class="detail-value"><?php echo date('h:i A', strtotime($booking['departure_time'])); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Seats</span>
                                <span class="detail-value"><?php echo $booking['number_of_seats']; ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Amount</span>
                                <span class="detail-value">₱<?php echo number_format($booking['total_amount'], 2); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Status</span>
                                <span class="detail-value status-<?php echo $booking['payment_status']; ?>">
                                    <?php echo ucfirst($booking['payment_status']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="actions">
                            <a href="booking-confirmation.php?booking_id=<?php echo $booking['id']; ?>" class="view-button">View Details</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="actions" style="margin-top: 20px;">
                <a href="index.php" class="button">Back to Home</a>
            </div>
        </div>
    </div>
</body>
</html>
