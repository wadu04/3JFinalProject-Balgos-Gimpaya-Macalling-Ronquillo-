<?php
session_start();
require_once '../app/config/database.php';

// Create database connection
$database = new Database();
$conn = $database->getConnection();

// Fetch all services
$stmt = $conn->prepare("SELECT * FROM services");
$stmt->execute();
$services = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spa Services</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .service-card {
            transition: transform 0.3s;
            height: 100%;
        }
        .service-card:hover {
            transform: translateY(-5px);
        }
        .card-img-top {
            height: 200px;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container my-5">
        <h1 class="text-center mb-5">Our Spa Services</h1>
        
        <div class="row g-4">
            <!-- Swedish Massage -->
            <div class="col-md-6 col-lg-3">
                <div class="card service-card shadow">
                    <img src="assets/images/swedish.jpg" class="card-img-top" alt="Swedish Massage">
                    <div class="card-body">
                        <h5 class="card-title">Swedish Massage</h5>
                        <p class="card-text">Full body massage using long strokes and kneading techniques</p>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-clock me-2"></i>60-90 minutes</li>
                            <li><i class="fas fa-peso-sign me-2"></i>900</li>
                        </ul>
                        <a href="book-appointment.php?service=1" class="btn btn-primary w-100">Book Now</a>
                    </div>
                </div>
            </div>

            <!-- Hilot -->
            <div class="col-md-6 col-lg-3">
                <div class="card service-card shadow">
                    <img src="assets/images/hilot.jpg" class="card-img-top" alt="Hilot">
                    <div class="card-body">
                        <h5 class="card-title">Hilot</h5>
                        <p class="card-text">Uses coconut oil or herbal compresses to restore energy balance and alleviate physical discomfort</p>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-clock me-2"></i>70 minutes</li>
                            <li><i class="fas fa-peso-sign me-2"></i>1,500</li>
                        </ul>
                        <a href="book-appointment.php?service=2" class="btn btn-primary w-100">Book Now</a>
                    </div>
                </div>
            </div>

            <!-- Thai Massage -->
            <div class="col-md-6 col-lg-3">
                <div class="card service-card shadow">
                    <img src="assets/images/thai.jpg" class="card-img-top" alt="Thai Massage">
                    <div class="card-body">
                        <h5 class="card-title">Thai Massage</h5>
                        <p class="card-text">Combines assisted stretching and acupressure, without the use of oil</p>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-clock me-2"></i>40 minutes</li>
                            <li><i class="fas fa-peso-sign me-2"></i>800</li>
                        </ul>
                        <a href="book-appointment.php?service=3" class="btn btn-primary w-100">Book Now</a>
                    </div>
                </div>
            </div>

            <!-- Hot Stone Massage -->
            <div class="col-md-6 col-lg-3">
                <div class="card service-card shadow">
                    <img src="assets/images/hot-stone.jpg" class="card-img-top" alt="Hot Stone Massage">
                    <div class="card-body">
                        <h5 class="card-title">Hot Stone Massage</h5>
                        <p class="card-text">Heated stones are used alongside massage strokes for deeper relaxation</p>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-clock me-2"></i>60 minutes</li>
                            <li><i class="fas fa-peso-sign me-2"></i>1,550</li>
                        </ul>
                        <a href="book-appointment.php?service=4" class="btn btn-primary w-100">Book Now</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
