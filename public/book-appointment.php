<?php
session_start();
require_once '../app/config/database.php';

$database = new Database();
$conn = $database->getConnection();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$service_id = isset($_GET['service']) ? $_GET['service'] : null;

if (!$service_id) {
    header('Location: services.php');
    exit();
}


$stmt = $conn->prepare("SELECT * FROM services WHERE id = ?");
$stmt->bind_param("i", $service_id);
$stmt->execute();
$service = $stmt->get_result()->fetch_assoc();

if (!$service) {
    header('Location: services.php');
    exit();
}

// Fetch therapists
$stmt = $conn->prepare("SELECT id, fullname FROM users WHERE role = 'therapist'");
$stmt->execute();
$therapists = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $therapist_id = filter_input(INPUT_POST, 'therapist_id', FILTER_VALIDATE_INT);
    $appointment_date = filter_input(INPUT_POST, 'appointment_date', FILTER_SANITIZE_STRING);
    $appointment_time = filter_input(INPUT_POST, 'appointment_time', FILTER_SANITIZE_STRING);
    
    if ($therapist_id && $appointment_date && $appointment_time) {
        $start_datetime = date('Y-m-d H:i:s', strtotime("$appointment_date $appointment_time"));
        $end_datetime = date('Y-m-d H:i:s', strtotime($start_datetime . " + {$service['duration']} minutes"));
        
        // Create appointment
        $stmt = $conn->prepare("
            INSERT INTO appointments (user_id, therapist_id, service_id, start_time, end_time, status) 
            VALUES (?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->bind_param("iiiss", 
            $_SESSION['user_id'],
            $therapist_id,
            $service_id,
            $start_datetime,
            $end_datetime
        );
        
        if ($stmt->execute()) {
            $appointment_id = $stmt->insert_id;
            header("Location: payment.php?appointment_id=" . $appointment_id);
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
    <title>Book Appointment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        .calendar-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 30px;
        }
        .time-slot {
            padding: 10px 15px;
            margin: 5px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-block;
        }
        .time-slot:hover {
            background-color: #f8f9fa;
            border-color: #0d6efd;
        }
        .time-slot.selected {
            background-color: #0d6efd;
            color: white;
            border-color: #0d6efd;
        }
        .booking-summary {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            padding: 20px;
            position: sticky;
            top: 20px;
        }
        .flatpickr-calendar {
            box-shadow: none !important;
            width: 100% !important;
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>

    <div class="container my-5">
        <div class="row">
            <div class="col-lg-8">
                <div class="calendar-container">
                    <h2 class="mb-4">Schedule Your Appointment</h2>
                    
                    <form id="bookingForm" method="POST">
                        <!-- Therapist Selection -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Select Therapist</label>
                            <select class="form-select form-select-lg" name="therapist_id" id="therapist_id" required>
                                <option value="">Choose a therapist...</option>
                                <?php foreach ($therapists as $therapist): ?>
                                    <option value="<?php echo $therapist['id']; ?>">
                                        <?php echo htmlspecialchars($therapist['fullname']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Date Selection -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Select Date</label>
                            <input type="text" class="form-control form-control-lg" id="appointment_date" name="appointment_date" required>
                        </div>

                        <!-- Time Selection -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Select Time</label>
                            <div id="timeSlots" class="d-flex flex-wrap">
                                <?php
                                $start_hour = 9; // 9 AM
                                $end_hour = 18; // 6 PM
                                for ($hour = $start_hour; $hour < $end_hour; $hour++) {
                                    foreach (['00', '30'] as $minute) {
                                        $time = sprintf("%02d:%s", $hour, $minute);
                                        echo "<div class='time-slot' data-time='$time'>$time</div>";
                                    }
                                }
                                ?>
                            </div>
                            <input type="hidden" name="appointment_time" id="appointment_time" required>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="booking-summary">
                    <h3>Booking Summary</h3>
                    <hr>
                    <div class="mb-4">
                        <h5><?php echo htmlspecialchars($service['service_name']); ?></h5>
                        <p class="text-muted"><?php echo htmlspecialchars($service['description']); ?></p>
                        <p><strong>Duration:</strong> <?php echo $service['duration']; ?> minutes</p>
                        <p><strong>Price:</strong> $<?php echo number_format($service['price'], 2); ?></p>
                    </div>
                    
                    <div class="mb-4">
                        <h5>Selected Details</h5>
                        <p class="mb-1"><strong>Therapist:</strong> <span id="selected_therapist">Not selected</span></p>
                        <p class="mb-1"><strong>Date:</strong> <span id="selected_date">Not selected</span></p>
                        <p><strong>Time:</strong> <span id="selected_time">Not selected</span></p>
                    </div>

                    <button type="submit" form="bookingForm" class="btn btn-primary btn-lg w-100" id="bookButton" disabled>
                        Proceed to Payment
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize flatpickr calendar
            const calendar = flatpickr("#appointment_date", {
                minDate: "today",
                dateFormat: "Y-m-d",
                disable: [
                    function(date) {
                        return (date.getDay() === 0); // Disable Sundays
                    }
                ],
                onChange: function(selectedDates, dateStr) {
                    document.getElementById('selected_date').textContent = dateStr;
                    updateBookButton();
                }
            });

            // Therapist selection
            const therapistSelect = document.getElementById('therapist_id');
            therapistSelect.addEventListener('change', function() {
                const selectedText = this.options[this.selectedIndex].text;
                document.getElementById('selected_therapist').textContent = selectedText;
                updateBookButton();
            });

            // Time slot selection
            const timeSlots = document.querySelectorAll('.time-slot');
            const appointmentTimeInput = document.getElementById('appointment_time');

            timeSlots.forEach(slot => {
                slot.addEventListener('click', function() {
                    timeSlots.forEach(s => s.classList.remove('selected'));
                    this.classList.add('selected');
                    const selectedTime = this.dataset.time;
                    appointmentTimeInput.value = selectedTime;
                    document.getElementById('selected_time').textContent = selectedTime;
                    updateBookButton();
                });
            });

            // Update book button state
            function updateBookButton() {
                const bookButton = document.getElementById('bookButton');
                const isComplete = 
                    therapistSelect.value && 
                    document.getElementById('appointment_date').value &&
                    appointmentTimeInput.value;
                
                bookButton.disabled = !isComplete;
            }
        });
    </script>
</body>
</html>
