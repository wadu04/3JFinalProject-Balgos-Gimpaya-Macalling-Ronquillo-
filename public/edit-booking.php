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

// Get available bus classes
$classes_query = "SELECT * FROM bus_classes";
$classes_stmt = $db->prepare($classes_query);
$classes_stmt->execute();
$bus_classes = $classes_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get schedules with available seats
$schedules_query = "SELECT s.*, bc.class_name,
                   (SELECT COUNT(*) FROM bookings 
                    WHERE schedule_id = s.id 
                    AND payment_status IN ('pending', 'confirmed')) as booked_seats,
                   bc.total_seats
                   FROM schedules s
                   LEFT JOIN bus_classes bc ON s.bus_class_id = bc.id
                   WHERE s.departure_time > NOW()
                   ORDER BY s.departure_time ASC";
$schedules_stmt = $db->prepare($schedules_query);
$schedules_stmt->execute();
$schedules = $schedules_stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Start transaction
        $db->beginTransaction();

        // Get current booking details
        $booking_query = "SELECT * FROM bookings WHERE id = ? AND user_id = ? AND payment_status = 'pending'";
        $booking_stmt = $db->prepare($booking_query);
        $booking_stmt->execute([$_POST['booking_id'], $_SESSION['user_id']]);
        $booking = $booking_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$booking) {
            throw new Exception("Booking not found or cannot be modified");
        }

        // Update booking
        $update_query = "UPDATE bookings 
                        SET schedule_id = ?, 
                            bus_class_id = ?, 
                            seat_number = ?,
                            modified_at = NOW()
                        WHERE id = ? AND user_id = ? AND payment_status = 'pending'";
        
        $update_stmt = $db->prepare($update_query);
        $result = $update_stmt->execute([
            $_POST['schedule_id'],
            $_POST['bus_class_id'],
            $_POST['seat_number'],
            $_POST['booking_id'],
            $_SESSION['user_id']
        ]);

        if ($result) {
            $db->commit();
            $_SESSION['message'] = "Booking updated successfully";
            header('Location: dashboard.php');
            exit();
        } else {
            throw new Exception("Failed to update booking");
        }
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['error'] = $e->getMessage();
        header('Location: dashboard.php');
        exit();
    }
}

// Get booking details if ID is provided
if (isset($_GET['id'])) {
    try {
        $booking_query = "SELECT b.*, s.departure_time, bc.class_name 
                         FROM bookings b
                         JOIN schedules s ON b.schedule_id = s.id
                         JOIN bus_classes bc ON b.bus_class_id = bc.id
                         WHERE b.id = ? AND b.user_id = ? AND b.payment_status = 'pending'";
        $booking_stmt = $db->prepare($booking_query);
        $booking_stmt->execute([$_GET['id'], $_SESSION['user_id']]);
        $booking = $booking_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$booking) {
            $_SESSION['error'] = "Booking not found or cannot be modified";
            header('Location: dashboard.php');
            exit();
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header('Location: dashboard.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Booking</title>
    <link rel="stylesheet" href="css/register-style.css">
    <style>
        .edit-form {
            max-width: 600px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #333;
        }
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        .form-control:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
        }
        .btn-primary {
            background: #007bff;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            width: 100%;
        }
        .btn-primary:hover {
            background: #0056b3;
            transform: translateY(-1px);
        }
        .btn-primary:active {
            transform: translateY(0);
        }
        .seats-info {
            font-size: 0.875rem;
            color: #666;
            margin-top: 0.25rem;
        }
        .seats-available {
            color: #28a745;
        }
        .seats-limited {
            color: #ffc107;
        }
        .seats-full {
            color: #dc3545;
        }
        .header-section {
            text-align: center;
            margin-bottom: 2rem;
        }
        .header-section h2 {
            color: #333;
            margin-bottom: 0.5rem;
        }
        .header-section p {
            color: #666;
            font-size: 0.9rem;
        }
        .form-footer {
            margin-top: 2rem;
            text-align: center;
        }
        .back-link {
            display: inline-block;
            color: #6c757d;
            text-decoration: none;
            margin-top: 1rem;
            font-size: 0.9rem;
        }
        .back-link:hover {
            color: #343a40;
        }
        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 6px;
            font-size: 0.9rem;
        }
        .alert-info {
            background-color: #cce5ff;
            border: 1px solid #b8daff;
            color: #004085;
        }
    </style>
</head>
<body>
    <div class="edit-form">
        <div class="header-section">
            <h2>Edit Booking</h2>
            <p>Update your booking details below. Only pending bookings can be modified.</p>
        </div>

        <?php if (isset($booking) && $booking['payment_status'] === 'pending'): ?>
        <div class="alert alert-info">
            Booking ID: #<?= htmlspecialchars($booking['id']) ?>
        </div>

        <form method="POST" action="edit-booking.php">
            <input type="hidden" name="booking_id" value="<?= htmlspecialchars($booking['id']) ?>">
            
            <div class="form-group">
                <label for="schedule_id">Schedule (Departure Time)</label>
                <select name="schedule_id" id="schedule_id" class="form-control" required>
                    <?php foreach ($schedules as $schedule): 
                        $available_seats = $schedule['total_seats'] - $schedule['booked_seats'];
                        $seats_class = $available_seats > 10 ? 'seats-available' : ($available_seats > 0 ? 'seats-limited' : 'seats-full');
                    ?>
                        <option value="<?= $schedule['id'] ?>" 
                                <?= $booking['schedule_id'] == $schedule['id'] ? 'selected' : '' ?>
                                <?= $available_seats <= 0 ? 'disabled' : '' ?>>
                            <?= htmlspecialchars(date('F j, Y g:i A', strtotime($schedule['departure_time']))) ?> - 
                            <?= htmlspecialchars($schedule['class_name']) ?>
                            (<?= $available_seats ?> seats available)
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="seats-info">Available seats will be shown for each schedule</div>
            </div>

            <div class="form-group">
                <label for="bus_class_id">Bus Class</label>
                <select name="bus_class_id" id="bus_class_id" class="form-control" required>
                    <?php foreach ($bus_classes as $class): ?>
                        <option value="<?= $class['id'] ?>" <?= $booking['bus_class_id'] == $class['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($class['class_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="seat_number">Seat Number</label>
                <input type="number" name="seat_number" id="seat_number" class="form-control" 
                       value="<?= htmlspecialchars($booking['seat_number']) ?>" 
                       min="1" max="50" required>
                <div class="seats-info">Choose a seat number between 1 and 50</div>
            </div>

            <button type="submit" class="btn-primary">Update Booking</button>

            <div class="form-footer">
                <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
            </div>
        </form>
        <?php endif; ?>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const scheduleSelect = document.getElementById('schedule_id');
        const seatInput = document.getElementById('seat_number');
        const busClassSelect = document.getElementById('bus_class_id');

        // Update available seats when schedule changes
        scheduleSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const availableSeats = parseInt(selectedOption.textContent.match(/\((\d+) seats available\)/)[1]);
            seatInput.max = availableSeats;
            
            if (parseInt(seatInput.value) > availableSeats) {
                seatInput.value = availableSeats;
            }
        });

        // Validate seat number on input
        seatInput.addEventListener('input', function() {
            const selectedOption = scheduleSelect.options[scheduleSelect.selectedIndex];
            const availableSeats = parseInt(selectedOption.textContent.match(/\((\d+) seats available\)/)[1]);
            
            if (parseInt(this.value) > availableSeats) {
                this.value = availableSeats;
            }
            if (parseInt(this.value) < 1) {
                this.value = 1;
            }
        });
    });
    </script>
</body>
</html>
