<?php
session_start();
require_once '../app/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Get all appointments with related information and review status
$query = "SELECT a.*, s.service_name, s.description as service_description, s.duration, s.price,
          u.fullname as therapist_name,
          p.payment_method, p.payment_status, p.transaction_id, p.payment_date,
          (SELECT COUNT(*) FROM reviews r WHERE r.appointment_id = a.id) as has_review
          FROM appointments a
          JOIN services s ON a.service_id = s.id
          JOIN users u ON a.therapist_id = u.id
          LEFT JOIN payments p ON a.id = p.appointment_id
          WHERE a.user_id = ?
          ORDER BY a.start_time DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$appointments = [];
while ($row = $result->fetch_assoc()) {
    $appointments[] = $row;
}

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
    <title>My Appointments - Gooding Spa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .rating {
            display: inline-block;
            direction: rtl;
        }
        .rating input {
            display: none;
        }
        .rating label {
            cursor: pointer;
            width: 25px;
            font-size: 25px;
            color: #ddd;
            padding: 5px;
        }
        .rating label:before {
            content: '★';
        }
        .rating input:checked ~ label {
            color: #ffd700;
        }
        .rating label:hover,
        .rating label:hover ~ label {
            color: #ffd700;
        }
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
                            <?php if (isset($appointment['payment_method']) && !empty($appointment['payment_method'])): ?>
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

                                <?php if (isset($appointment['payment_method']) && !empty($appointment['payment_method'])): ?>
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
                                        <?php if (isset($appointment['transaction_id']) && !empty($appointment['transaction_id'])): ?>
                                            <small class="text-muted d-block mt-1">
                                                Transaction ID: <?php echo htmlspecialchars($appointment['transaction_id']); ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($appointment['status'] === 'completed'): ?>
                                    <?php if (!$appointment['has_review']): ?>
                                        <div class="d-grid gap-2">
                                            <button type="button" class="btn btn-primary btn-sm"
                                                    onclick="openReviewModal(<?php echo $appointment['id']; ?>, 
                                                            '<?php echo htmlspecialchars($appointment['therapist_name']); ?>')">
                                                <i class="fas fa-star"></i> Review
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <span class="badge bg-success">
                                            <i class="fas fa-check"></i> Reviewed
                                        </span>
                                    <?php endif; ?>
                                <?php elseif ($appointment['status'] === 'pending'): ?>
                                    <div class="d-grid gap-2">
                                        <button type="button" class="btn btn-danger btn-sm"
                                                onclick="if(confirm('Are you sure you want to cancel this appointment?'))
                                                        window.location.href='cancel-appointment.php?id=<?php echo $appointment['id']; ?>'">
                                            <i class="fas fa-times"></i> Cancel
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Review Modal -->
    <div class="modal fade" id="reviewModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Review Your Experience</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="reviewForm">
                    <div class="modal-body">
                        <input type="hidden" name="appointment_id" id="appointment_id">
                        
                        <p>How was your experience with <strong><span id="therapist_name"></span></strong>?</p>
                        
                        <div class="mb-3 text-center">
                            <div class="rating">
                                <input type="radio" name="rating" value="5" id="star5" required>
                                <label for="star5" title="5 stars"></label>
                                <input type="radio" name="rating" value="4" id="star4">
                                <label for="star4" title="4 stars"></label>
                                <input type="radio" name="rating" value="3" id="star3">
                                <label for="star3" title="3 stars"></label>
                                <input type="radio" name="rating" value="2" id="star2">
                                <label for="star2" title="2 stars"></label>
                                <input type="radio" name="rating" value="1" id="star1">
                                <label for="star1" title="1 star"></label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="comment" class="form-label">Share your experience (optional)</label>
                            <textarea class="form-control" id="comment" name="comment" rows="3" 
                                    placeholder="Tell us about your experience..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="submitReview()">Submit Review</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const reviewModal = new bootstrap.Modal(document.getElementById('reviewModal'));
        
        function openReviewModal(appointmentId, therapistName) {
            document.getElementById('appointment_id').value = appointmentId;
            document.getElementById('therapist_name').textContent = therapistName;
            reviewModal.show();
        }

        function submitReview() {
            const form = document.getElementById('reviewForm');
            const formData = new FormData(form);

            // Validate rating
            const rating = formData.get('rating');
            if (!rating) {
                alert('Please select a rating');
                return;
            }

            fetch('submit-review.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    reviewModal.hide();
                    window.location.href = 'appointments.php?success=' + encodeURIComponent('Thank you for your review!');
                } else {
                    alert(data.error || 'Failed to submit review');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to submit review. Please try again.');
            });
        }
    </script>
</body>
</html>
