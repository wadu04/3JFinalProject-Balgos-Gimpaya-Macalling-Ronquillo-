<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="css/styles.css">
  <title>Bus Line</title>
  <style>
    .user-menu {
      position: relative;
      display: inline-block;
    }

    .user-menu-content {
      display: none;
      position: absolute;
      right: 0;
      background-color: #f9f9f9;
      min-width: 160px;
      box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
      z-index: 1;
      border-radius: 5px;
    }

    .user-menu-content a {
      color: black;
      padding: 12px 16px;
      text-decoration: none;
      display: block;
    }

    .user-menu-content a:hover {
      background-color: #f1f1f1;
    }

    .user-menu:hover .user-menu-content {
      display: block;
    }

    .username-display {
      color: #333;
      padding: 10px 18px;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .username-display i {
      font-size: 20px;
    }
  </style>
</head>

<body>
  <header>
    <nav class="navbar section-content">
      <a href="#" class="nav-logo">
        <h2 class="logo-text">Bus Line</h2>
      </a>
      <ul class="nav-menu">
        <li>
          <a href="#Home" class="nav-link">Home</a>
        </li>
        <li>
          <a href="#" class="nav-link">Service</a>
        </li>
        <?php if (!isset($_SESSION['username'])): ?>
        <!-- End Role Selection Dropdown     <li>
          <select class="nav-link" style="padding: 10px 18px; border-radius: 50px; font-size: medium;" onchange="navigateToPage(this)">
            <option value="" disabled selected>Select Role</option>
            <option value="index.php">User</option> 
            <option value="dashboard.php">Admin</option>  
          </select>
        </li>-->
        <li>
          <a href="login-form.php" class="nav-link" style="border: 1px solid;">Login</a>
        </li>
        
        <?php else: ?>
        <li>
          <div class="user-menu">
            <div class="username-display">
              <i class='bx bxs-user-circle'></i>
              <?php echo htmlspecialchars($_SESSION['username']); ?>
              <i class='bx bx-chevron-down'></i>
            </div>
            <div class="user-menu-content">
              <?php if (isset($_SESSION['user_id'])): ?>
                <a href="booking-form.php">Book a Ticket</a>
                <a href="booking-history.php">My Bookings</a>
                <a href="logout.php">Logout</a>
              <?php else: ?>
                <a href="login-form.php">Login</a>
                <a href="register-form.php">Register</a>
              <?php endif; ?>
            </div>
          </div>
        </li>
        <?php endif; ?>
      </ul>
    </nav>
  </header>

  <main>
    <section class="hero-section" id="Home">
      <div class="section-content">
        <div class="hero-details">
          <h2 class="title">Bus Line</h2>
          <h3 class="subtitle">Reserve a ticket now</h3>
          <p class="description">Lorem ipsum dolor sit amet consectetur adipisicing elit. Nesciunt, consequatur repellendus. Natus facilis, quaerat repudiandae maiores voluptate hic delectus quibusdam?</p>
          <div class="buttons">
            <a href="#" class="button explore-now">Service</a>
            <a href="<?php echo isset($_SESSION['user_id']) ? 'booking-form.php' : 'login-form.php'; ?>" 
               class="button contact-us">Book Now</a>
          </div>
        </div>
        <div class="hero-image-wrapper">
          <img src="img/bus.png" alt="Hero" class="hero-image">
        </div>
      </div>
    </section>
  </main>

  <script>
    function navigateToPage(selectElement) {
      const selectedValue = selectElement.value; 
      if (selectedValue) { 
        window.location.href = selectedValue; 
      }
    }
  </script>
</body>

</html>
