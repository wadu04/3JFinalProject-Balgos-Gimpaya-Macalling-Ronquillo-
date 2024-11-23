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

// Fetch bus classes
$class_query = "SELECT * FROM bus_classes";
try {
    $class_stmt = $db->query($class_query);
    $bus_classes = $class_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch schedules
    $schedule_query = "SELECT s.*, bc.class_name, bc.price 
                      FROM schedules s 
                      JOIN bus_classes bc ON s.bus_class_id = bc.id 
                      WHERE s.available_seats > 0";
    $schedule_stmt = $db->query($schedule_query);
    $schedules = $schedule_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Ticket</title>
    <link rel="stylesheet" href="css/register-style.css">
    <style>
        .bus-layout {
            max-width: 600px;
            margin: 20px auto;
            background: #f5f5f5;
            padding: 20px;
            border-radius: 10px;
        }
        .seat-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .seat-group {
            display: flex;
            gap: 10px;
        }
        .seat {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            background: #fff;
            transition: all 0.3s ease;
        }
        .seat.selected {
            background: #4CAF50;
            color: white;
            border-color: #45a049;
        }
        .seat.booked {
            background: #ff5252;
            color: white;
            border-color: #ff1744;
            cursor: not-allowed;
        }
        .seat:hover:not(.booked) {
            background: #e8f5e9;
            border-color: #4CAF50;
        }
        .bus-front {
            width: 100%;
            height: 60px;
            background: #333;
            border-radius: 20px 20px 0 0;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        .selected-seats {
            margin-top: 20px;
            padding: 10px;
            background: #e8f5e9;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="registration-form">
            <h2>Book a Ticket</h2>
            <p>Baguio to Isabela</p>
            
            <form id="bookingForm">
                <div class="form-group">
                    <label for="busClass">Bus Class:</label>
                    <select name="busClass" id="busClass" required>
                        <option value="">Select Bus Class</option>
                        <?php foreach ($bus_classes as $class): ?>
                            <option value="<?= $class['id'] ?>" data-price="<?= $class['price'] ?>">
                                <?= $class['class_name'] ?> - ₱<?= number_format($class['price'], 2) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="schedule">Schedule:</label>
                    <select name="schedule" id="schedule" required>
                        <option value="">Select Schedule</option>
                        <?php foreach ($schedules as $schedule): ?>
                            <option value="<?= $schedule['departure_time'] ?>" 
                                    data-class="<?= $schedule['bus_class_id'] ?>"
                                    data-available="<?= $schedule['available_seats'] ?>">
                                <?= date('h:i A', strtotime($schedule['departure_time'])) ?> 
                                (<?= $schedule['class_name'] ?> - <?= $schedule['available_seats'] ?> seats available)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Select Your Seats:</label>
                    <div class="bus-layout">
                        <div class="bus-front">DRIVER</div>
                        <div id="seatLayout"></div>
                    </div>
                    <div class="selected-seats">
                        Selected Seats: <span id="selectedSeatsDisplay">None</span>
                        <input type="hidden" name="selectedSeats" id="selectedSeats">
                    </div>
                </div>

                <div class="form-group">
                    <label for="payment">Payment Method:</label>
                    <select name="payment" id="payment" required>
                        <option value="">Select Payment Method</option>
                        <option value="gcash">GCash</option>
                        <option value="cash">Cash</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Total Amount: ₱<span id="totalAmount">0.00</span></label>
                </div>

                <div id="message" class="message"></div>
                
                <button type="submit" class="submit-btn">Proceed to Payment</button>
            </form>
        </div>
    </div>

    <script>
        let selectedSeats = [];
        const maxSeats = 3; // Maximum seats per booking

        function createSeatLayout() {
            const layout = document.getElementById('seatLayout');
            layout.innerHTML = '';
            
            // Create 10 rows with 6 seats each (3 on each side)
            for (let row = 1; row <= 10; row++) {
                const seatRow = document.createElement('div');
                seatRow.className = 'seat-row';
                
                // Left side seats
                const leftGroup = document.createElement('div');
                leftGroup.className = 'seat-group';
                for (let i = 1; i <= 3; i++) {
                    const seatNum = (row - 1) * 6 + i;
                    const seat = createSeat(seatNum);
                    leftGroup.appendChild(seat);
                }
                
                // Right side seats
                const rightGroup = document.createElement('div');
                rightGroup.className = 'seat-group';
                for (let i = 4; i <= 6; i++) {
                    const seatNum = (row - 1) * 6 + i;
                    const seat = createSeat(seatNum);
                    rightGroup.appendChild(seat);
                }
                
                seatRow.appendChild(leftGroup);
                seatRow.appendChild(rightGroup);
                layout.appendChild(seatRow);
            }
        }

        function createSeat(number) {
            const seat = document.createElement('div');
            seat.className = 'seat';
            seat.textContent = number;
            seat.dataset.seatNumber = number;
            
            seat.addEventListener('click', function() {
                if (this.classList.contains('booked')) return;
                
                if (this.classList.contains('selected')) {
                    this.classList.remove('selected');
                    selectedSeats = selectedSeats.filter(num => num !== number);
                } else {
                    if (selectedSeats.length >= maxSeats) {
                        alert('You can only select up to ' + maxSeats + ' seats.');
                        return;
                    }
                    this.classList.add('selected');
                    selectedSeats.push(number);
                }
                
                updateSelectedSeatsDisplay();
                calculateTotal();
            });
            
            return seat;
        }

        function updateSelectedSeatsDisplay() {
            const display = document.getElementById('selectedSeatsDisplay');
            const input = document.getElementById('selectedSeats');
            
            if (selectedSeats.length > 0) {
                display.textContent = selectedSeats.sort((a, b) => a - b).join(', ');
                input.value = selectedSeats.join(',');
            } else {
                display.textContent = 'None';
                input.value = '';
            }
        }

        function calculateTotal() {
            const busClassSelect = document.getElementById('busClass');
            const selectedOption = busClassSelect.options[busClassSelect.selectedIndex];
            
            if (selectedOption && selectedSeats.length > 0) {
                const price = parseFloat(selectedOption.dataset.price);
                const total = price * selectedSeats.length;
                document.getElementById('totalAmount').textContent = total.toFixed(2);
            } else {
                document.getElementById('totalAmount').textContent = '0.00';
            }
        }

        // Handle schedule change
        document.getElementById('schedule').addEventListener('change', function() {
            const scheduleSelect = document.getElementById('schedule');
            const busClassSelect = document.getElementById('busClass');
            
            if (scheduleSelect.value) {
                const selectedOption = scheduleSelect.options[scheduleSelect.selectedIndex];
                busClassSelect.value = selectedOption.dataset.class;
                
                // Reset seat selection
                selectedSeats = [];
                updateSelectedSeatsDisplay();
                calculateTotal();
                
                // Fetch and mark booked seats
                fetchBookedSeats(selectedOption.dataset.class, scheduleSelect.value);
            }
        });

        // Fetch booked seats from the server
        function fetchBookedSeats(busClassId, scheduleTime) {
            fetch(`get-booked-seats.php?bus_class_id=${busClassId}&schedule=${scheduleTime}`)
                .then(response => response.json())
                .then(data => {
                    // Reset all seats
                    document.querySelectorAll('.seat').forEach(seat => {
                        seat.classList.remove('booked');
                    });
                    
                    // Mark booked seats
                    data.booked_seats.forEach(seatNumber => {
                        const seat = document.querySelector(`.seat[data-seat-number="${seatNumber}"]`);
                        if (seat) {
                            seat.classList.add('booked');
                        }
                    });
                })
                .catch(error => console.error('Error fetching booked seats:', error));
        }

        // Form submission
        document.getElementById('bookingForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (selectedSeats.length === 0) {
                alert('Please select at least one seat.');
                return;
            }
            
            const formData = new FormData(this);
            formData.append('seats', selectedSeats.length);
            
            fetch('process-booking.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    window.location.href = `booking-confirmation.php?booking_id=${data.booking_id}`;
                } else {
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                window.location.href = 'index.php';
            });
        });

        // Initialize seat layout
        createSeatLayout();
    </script>
</body>
</html>
