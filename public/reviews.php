<?php
session_start();
require_once '../app/config/database.php';

$database = new Database();
$conn = $database->getConnection();

// Fetch all reviews with user and therapist information
$query = "SELECT r.*, a.therapist_id, u.fullname as user_name, t.fullname as therapist_name 
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Therapist Reviews - Spa Appointment System</title>
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
    </style>
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>

    <div class="container my-5">
        <h2 class="text-center mb-4">Therapist Reviews</h2>
        
        <div class="row">
            <?php if (!empty($reviews)): ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="col-md-6 mb-4">
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
                                    <small>Date: <?php echo date('F j, Y', strtotime($review['created_at'])); ?></small>
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
