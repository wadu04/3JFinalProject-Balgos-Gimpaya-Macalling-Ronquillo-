<?php
session_start();
require_once '../app/config/database.php';

// Check if user is logged in and booking ID is set
if (!isset($_SESSION['user_id']) || !isset($_GET['booking_id'])) {
    header('Location: index.php');
    exit();
}

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Fetch booking details with user information
$booking_query = "SELECT b.*, bc.class_name, bc.price, s.departure_time, u.fullname, 
                        GROUP_CONCAT(bs.seat_number ORDER BY bs.seat_number) as seat_numbers
                 FROM bookings b
                 JOIN bus_classes bc ON b.bus_class_id = bc.id
                 JOIN schedules s ON b.schedule_id = s.id
                 JOIN users u ON b.user_id = u.id
                 LEFT JOIN booked_seats bs ON b.id = bs.booking_id
                 WHERE b.id = ? AND b.user_id = ?
                 GROUP BY b.id";

$booking_stmt = $db->prepare($booking_query);
$booking_stmt->execute([$_GET['booking_id'], $_SESSION['user_id']]);
$booking = $booking_stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    header('Location: index.php');
    exit();
}

// Convert seat numbers string to array
$seat_numbers = explode(',', $booking['seat_numbers']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation</title>
    <link rel="stylesheet" href="css/register-style.css">
    <style>
        .confirmation-details {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            max-width: 600px;
            margin: 0 auto;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: bold;
            color: #666;
        }
        .detail-value {
            color: #333;
        }
        .total-amount {
            font-size: 1.2em;
            color: #4CAF50;
            font-weight: bold;
        }
        .seat-numbers {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }
        .seat-number {
            background: #4CAF50;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="registration-form">
            <h2>Booking Confirmation</h2>
            <div class="confirmation-details">
                <div class="detail-row">
                    <span class="detail-label">Booking ID:</span>
                    <span class="detail-value"><?= $booking['id'] ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Passenger Name:</span>
                    <span class="detail-value"><?= htmlspecialchars($booking['fullname']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Bus Class:</span>
                    <span class="detail-value"><?= htmlspecialchars($booking['class_name']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Departure Time:</span>
                    <span class="detail-value"><?= date('h:i A', strtotime($booking['departure_time'])) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Number of Seats:</span>
                    <span class="detail-value"><?= $booking['number_of_seats'] ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Seat Numbers:</span>
                    <div class="seat-numbers">
                        <?php foreach ($seat_numbers as $seat): ?>
                            <span class="seat-number"><?= $seat ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Payment Method:</span>
                    <span class="detail-value"><?= ucfirst(htmlspecialchars($booking['payment_method'])) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Payment Status:</span>
                    <span class="detail-value"><?= ucfirst($booking['payment_status']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Total Amount:</span>
                    <span class="detail-value total-amount">₱<?= number_format($booking['total_amount'], 2) ?></span>
                </div>
            </div>
            <div style="text-align: center; margin-top: 20px;">
                <button onclick="window.print()" class="submit-btn">Print Ticket</button>
                <a href="index.php" class="submit-btn" style="text-decoration: none; display: inline-block; margin-left: 10px;">Back to Home</a>
            </div>
        </div>
    </div>
</body>
</html>
