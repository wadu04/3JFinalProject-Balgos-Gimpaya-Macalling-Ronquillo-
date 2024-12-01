<?php
session_start();
require_once '../app/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Get user's appointments with all necessary information
$query = "SELECT a.*, s.service_name, s.price, s.duration, s.description,
          u.fullname as therapist_name, 
          p.payment_status, p.payment_method, p.transaction_id,
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .appointment-card {
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
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
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container my-4">
        <h2 class="mb-4">My Appointments</h2>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_GET['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (empty($appointments)): ?>
            <div class="alert alert-info">
                You don't have any appointments yet. 
                <a href="services.php" class="alert-link">Book an appointment now!</a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($appointments as $appointment): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card appointment-card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <?php echo htmlspecialchars($appointment['service_name']); ?>
                                    <?php
                                    $status_badges = [
                                        'pending' => 'warning',
                                        'confirmed' => 'info',
                                        'completed' => 'success',
                                        'cancelled' => 'danger'
                                    ];
                                    $badge_color = $status_badges[$appointment['status']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $badge_color; ?> float-end">
                                        <?php echo ucfirst($appointment['status']); ?>
                                    </span>
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <strong>Date:</strong> <?php echo date('F j, Y', strtotime($appointment['start_time'])); ?><br>
                                    <strong>Time:</strong> <?php echo date('g:i A', strtotime($appointment['start_time'])); ?><br>
                                    <strong>Duration:</strong> <?php echo $appointment['duration']; ?> minutes<br>
                                    <strong>Price:</strong> ₱<?php echo number_format($appointment['price'], 2); ?><br>
                                    <strong>Therapist:</strong> <?php echo htmlspecialchars($appointment['therapist_name']); ?>
                                </div>

                                <?php if ($appointment['payment_status']): ?>
                                    <div class="mb-3">
                                        <strong>Payment Status:</strong> 
                                        <span class="badge bg-<?php echo $appointment['payment_status'] === 'paid' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($appointment['payment_status']); ?>
                                        </span><br>
                                        <?php if ($appointment['payment_method']): ?>
                                            <strong>Payment Method:</strong> <?php echo ucfirst($appointment['payment_method']); ?><br>
                                        <?php endif; ?>
                                        <?php if ($appointment['transaction_id']): ?>
                                            <strong>Transaction ID:</strong> <?php echo htmlspecialchars($appointment['transaction_id']); ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="mt-3">
                                    <?php if ($appointment['status'] === 'completed'): ?>
                                        <?php if (!$appointment['has_review']): ?>
                                            <button type="button" class="btn btn-primary" 
                                                    onclick="openReviewModal(<?php echo $appointment['id']; ?>, 
                                                           '<?php echo htmlspecialchars($appointment['therapist_name']); ?>')">
                                                <i class="fas fa-star"></i> Review Therapist
                                            </button>
                                        <?php else: ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check"></i> Review Submitted
                                            </span>
                                        <?php endif; ?>
                                    <?php elseif ($appointment['status'] === 'pending' || 
                                              ($appointment['status'] === 'confirmed' && 
                                               $appointment['payment_status'] !== 'paid')): ?>
                                        <div class="d-flex gap-2">
                                            <?php if ($appointment['payment_status'] !== 'paid'): ?>
                                                <a href="payment.php?appointment_id=<?php echo $appointment['id']; ?>" 
                                                   class="btn btn-primary">
                                                    <i class="fas fa-credit-card"></i> Pay Now
                                                </a>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-danger" 
                                                    onclick="if(confirm('Are you sure you want to cancel this appointment?')) 
                                                            window.location.href='cancel-appointment.php?id=<?php echo $appointment['id']; ?>'">
                                                <i class="fas fa-times"></i> Cancel
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
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
                    window.location.href = 'my-appointments.php?success=' + encodeURIComponent('Thank you for your review!');
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
