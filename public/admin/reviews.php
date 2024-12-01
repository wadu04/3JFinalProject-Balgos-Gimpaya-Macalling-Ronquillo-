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

// Fetch all reviews with user and therapist information
$query = "SELECT r.*, a.therapist_id, u.fullname as user_name, t.fullname as therapist_name,
          a.start_time as appointment_date
          FROM reviews r 
          JOIN appointments a ON r.appointment_id = a.id 
          JOIN users u ON r.user_id = u.id 
          JOIN users t ON a.therapist_id = t.id 
          ORDER BY r.created_at DESC";
$result = $conn->query($query);
$reviews = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }
}

// Calculate average ratings per therapist
$therapist_ratings = "SELECT 
    t.id,
    t.fullname as therapist_name,
    COUNT(r.id) as total_reviews,
    ROUND(AVG(r.rating), 1) as average_rating
    FROM users t
    LEFT JOIN appointments a ON t.id = a.therapist_id
    LEFT JOIN reviews r ON a.id = r.appointment_id
    WHERE t.role = 'therapist'
    GROUP BY t.id, t.fullname
    ORDER BY average_rating DESC";
$ratings_result = $conn->query($therapist_ratings);
$therapist_stats = [];
if ($ratings_result) {
    while ($row = $ratings_result->fetch_assoc()) {
        $therapist_stats[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviews Management - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .rating {
            color: #ffd700;
        }
        .review-card {
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stats-card {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include 'admin-navbar.php'; ?>

    <div class="container-fluid my-4">
        <h2 class="mb-4">Reviews Management</h2>

        <!-- Therapist Statistics -->
        <div class="row mb-4">
            <div class="col-12">
                <h3>Therapist Ratings Overview</h3>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Therapist</th>
                                <th>Average Rating</th>
                                <th>Total Reviews</th>
                                <th>Rating Distribution</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($therapist_stats as $stat): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($stat['therapist_name']); ?></td>
                                    <td>
                                        <div class="rating">
                                            <?php
                                            $rating = $stat['average_rating'] ?: 0;
                                            for ($i = 1; $i <= 5; $i++) {
                                                if ($i <= $rating) {
                                                    echo '<i class="fas fa-star"></i>';
                                                } elseif ($i - $rating < 1 && $i - $rating > 0) {
                                                    echo '<i class="fas fa-star-half-alt"></i>';
                                                } else {
                                                    echo '<i class="far fa-star"></i>';
                                                }
                                            }
                                            echo " (" . ($rating ?: '0.0') . ")";
                                            ?>
                                        </div>
                                    </td>
                                    <td><?php echo $stat['total_reviews']; ?></td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <?php if ($stat['total_reviews'] > 0): ?>
                                                <div class="progress-bar bg-success" 
                                                     style="width: <?php echo ($rating / 5) * 100; ?>%">
                                                </div>
                                            <?php else: ?>
                                                <div class="progress-bar" style="width: 0">No reviews yet</div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- All Reviews -->
        <h3>All Reviews</h3>
        <div class="row">
            <?php if (!empty($reviews)): ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card review-card">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($review['therapist_name']); ?></h5>
                                <div class="rating mb-2">
                                    <?php
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= $review['rating']) {
                                            echo '<i class="fas fa-star"></i>';
                                        } else {
                                            echo '<i class="far fa-star"></i>';
                                        }
                                    }
                                    ?>
                                </div>
                                <p class="card-text"><?php echo htmlspecialchars($review['comment']); ?></p>
                                <div class="text-muted">
                                    <small>Reviewed by: <?php echo htmlspecialchars($review['user_name']); ?></small><br>
                                    <small>Appointment Date: <?php echo date('F j, Y', strtotime($review['appointment_date'])); ?></small><br>
                                    <small>Review Date: <?php echo date('F j, Y', strtotime($review['created_at'])); ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        No reviews available yet.
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
