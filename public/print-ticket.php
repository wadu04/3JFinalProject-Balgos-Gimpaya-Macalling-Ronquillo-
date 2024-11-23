<?php
session_start();
require_once '../app/config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login-form.php');
    exit();
}

// Check if booking ID is provided
if (!isset($_GET['id'])) {
    die('Booking ID not provided');
}

$database = new Database();
$db = $database->getConnection();

try {
    // Get booking details
    $query = "SELECT b.*, u.fullname, u.email, s.departure_time, bc.class_name, bc.price
              FROM bookings b
              JOIN users u ON b.user_id = u.id
              JOIN schedules s ON b.schedule_id = s.id
              JOIN bus_classes bc ON b.bus_class_id = bc.id
              WHERE b.id = ? AND (b.user_id = ? OR ? = 'admin')";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$_GET['id'], $_SESSION['user_id'], $_SESSION['role']]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        die('Booking not found or access denied');
    }

    // Get booked seats
    $seats_query = "SELECT seat_number FROM booked_seats WHERE booking_id = ?";
    $seats_stmt = $db->prepare($seats_query);
    $seats_stmt->execute([$booking['id']]);
    $seats = $seats_stmt->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bus Ticket - <?= htmlspecialchars($booking['id']) ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .ticket {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .ticket-header {
            text-align: center;
            border-bottom: 2px solid #eee;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .ticket-content {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        .ticket-info {
            margin-bottom: 15px;
        }
        .ticket-info label {
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
            color: #666;
        }
        .ticket-info span {
            font-size: 1.1em;
        }
        .ticket-footer {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #eee;
            text-align: center;
        }
        .print-button {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.1em;
        }
        .print-button:hover {
            background: #45a049;
        }
        @media print {
            body {
                background: white;
            }
            .ticket {
                box-shadow: none;
            }
            .print-button {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="ticket-header">
            <h1>Bus Ticket</h1>
            <p>Booking Reference: #<?= str_pad($booking['id'], 6, '0', STR_PAD_LEFT) ?></p>
        </div>
        
        <div class="ticket-content">
            <div class="ticket-info">
                <label>Passenger Name</label>
                <span><?= htmlspecialchars($booking['fullname']) ?></span>
            </div>
            
            <div class="ticket-info">
                <label>Email</label>
                <span><?= htmlspecialchars($booking['email']) ?></span>
            </div>
            
            <div class="ticket-info">
                <label>Bus Class</label>
                <span><?= htmlspecialchars($booking['class_name']) ?></span>
            </div>
            
            <div class="ticket-info">
                <label>Departure Time</label>
                <span><?= date('M d, Y h:i A', strtotime($booking['departure_time'])) ?></span>
            </div>
            
            <div class="ticket-info">
                <label>Seat Numbers</label>
                <span><?= htmlspecialchars(implode(', ', $seats)) ?></span>
            </div>
            
            <div class="ticket-info">
                <label>Total Amount</label>
                <span>₱<?= number_format($booking['total_amount'], 2) ?></span>
            </div>
        </div>
        
        <div class="ticket-footer">
            <p>Status: <strong><?= ucfirst(htmlspecialchars($booking['payment_status'])) ?></strong></p>
            <button onclick="window.print()" class="print-button">Print Ticket</button>
        </div>
    </div>
</body>
</html>
