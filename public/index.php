<?php
session_start();
require_once '../app/config/database.php';

// Create database connection
$database = new Database();
$conn = $database->getConnection();

// Fetch featured services
$stmt = $conn->prepare("SELECT * FROM services LIMIT 4");
$stmt->execute();
$services = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gooding Spa - Relaxation & Wellness</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .hero {
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('assets/images/hero.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 150px 0;
            margin-top: -76px;
        }
        .service-card {
            transition: transform 0.3s;
            height: 100%;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .service-card:hover {
            transform: translateY(-5px);
        }
        .card-img-top {
            height: 200px;
            object-fit: cover;
        }
        .feature-icon {
            font-size: 2.5rem;
            color: #3498db;
            margin-bottom: 1rem;
        }
        .btn-primary {
            background-color: #3498db;
            border-color: #3498db;
        }
        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }
        .btn-outline-light:hover {
            background-color: rgba(255,255,255,0.9);
            color: #2c3e50;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container text-center">
            <h1 class="display-3 mb-4">Welcome to Gooding Spa</h1>
            <p class="lead mb-5">Experience ultimate relaxation and rejuvenation with our premium spa services</p>
            <div class="hero-buttons">
                <a href="book-appointment.php" class="btn btn-primary btn-lg">Book Now</a>
                <a href="services.php" class="btn btn-outline-light btn-lg">Our Services</a>
            </div>
        </div>
    </section>

    <!-- Featured Services -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Our Featured Services</h2>
            <div class="row g-4">
                <?php foreach ($services as $service): ?>
                    <div class="col-md-6 col-lg-3">
                        <div class="card service-card">
                            <img src="assets/images/<?php echo strtolower(str_replace(' ', '-', $service['service_name'])); ?>.jpg" 
                                 class="card-img-top" alt="<?php echo htmlspecialchars($service['service_name']); ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($service['service_name']); ?></h5>
                                <p class="card-text"><?php echo htmlspecialchars($service['description']); ?></p>
                                <ul class="list-unstyled">
                                    <li>Duration: <?php echo $service['duration']; ?> minutes</li>
                                    <li>Price: ₱<?php echo number_format($service['price'], 2); ?></li>
                                </ul>
                                <a href="book-appointment.php?service=<?php echo $service['id']; ?>" 
                                   class="btn btn-primary w-100">Book Now</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center mt-5">
                <a href="services.php" class="btn btn-primary btn-lg">View All Services</a>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-5">Why Choose Us</h2>
            <div class="row g-4">
                <div class="col-md-4 text-center">
                    <div class="feature-icon">
                        <i class="fas fa-certificate"></i>
                    </div>
                    <h4>Expert Therapists</h4>
                    <p>Our certified therapists provide professional and high-quality services</p>
                </div>
                <div class="col-md-4 text-center">
                    <div class="feature-icon">
                        <i class="fas fa-spa"></i>
                    </div>
                    <h4>Premium Facilities</h4>
                    <p>Modern and clean facilities designed for your comfort and relaxation</p>
                </div>
                <div class="col-md-4 text-center">
                    <div class="feature-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h4>Flexible Hours</h4>
                    <p>Open daily with convenient hours to fit your schedule</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="py-5">
        <div class="container text-center">
            <h2 class="mb-4">Ready to Experience Ultimate Relaxation?</h2>
            <p class="lead mb-4">Book your appointment today and let us take care of your well-being.</p>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="book-appointment.php" class="btn btn-primary btn-lg">Book Now</a>
            <?php else: ?>
                <a href="register.php" class="btn btn-primary btn-lg me-3">Sign Up</a>
                <a href="login.php" class="btn btn-outline-primary btn-lg">Login</a>
            <?php endif; ?>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
