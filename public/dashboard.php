<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../app/config/database.php';


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login-form.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    // Fetch all users
    $users_query = "SELECT * FROM users";
    $users_stmt = $db->prepare($users_query);
    $users_stmt->execute();
    $users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch all bookings
    if ($_SESSION['role'] === 'admin') {
        $bookings_query = "SELECT b.*, u.fullname, u.email, s.departure_time, bc.class_name 
                          FROM bookings b 
                          JOIN users u ON b.user_id = u.id 
                          JOIN schedules s ON b.schedule_id = s.id 
                          JOIN bus_classes bc ON b.bus_class_id = bc.id
                          ORDER BY b.booking_date DESC";
        $bookings_stmt = $db->prepare($bookings_query);
        $bookings_stmt->execute();
    } else {
        $bookings_query = "SELECT b.*, s.departure_time, bc.class_name 
                          FROM bookings b 
                          JOIN schedules s ON b.schedule_id = s.id 
                          JOIN bus_classes bc ON b.bus_class_id = bc.id
                          WHERE b.user_id = ?
                          ORDER BY b.booking_date DESC";
        $bookings_stmt = $db->prepare($bookings_query);
        $bookings_stmt->execute([$_SESSION['user_id']]);
    }
    $bookings = $bookings_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch bus classes
    $classes_query = "SELECT * FROM bus_classes";
    $classes_stmt = $db->prepare($classes_query);
    $classes_stmt->execute();
    $bus_classes = $classes_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch schedules
    $schedules_query = "SELECT s.*, bc.class_name 
                     FROM schedules s
                     LEFT JOIN bus_classes bc ON s.bus_class_id = bc.id";
    $schedules_stmt = $db->prepare($schedules_query);
    $schedules_stmt->execute();
    $schedules = $schedules_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="css/register-style.css">
    <style>
        .dashboard {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            overflow-x: auto;
        }
        .section h2 {
            margin-top: 0;
            color: #333;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            min-width: 800px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        tr:hover {
            background-color: #f9f9f9;
        }
        .btn {
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            color: white;
            text-decoration: none;
            font-size: 0.9em;
            margin: 2px;
            display: inline-block;
        }
        .btn-edit { background: #2196F3; }
        .btn-delete { background: #f44336; }
        .btn-view { background: #4CAF50; }
        .tab-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .tab-btn {
            padding: 10px 20px;
            border: none;
            background: #f8f9fa;
            cursor: pointer;
            margin-right: 5px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .tab-btn:hover {
            background: #e9ecef;
        }

        .tab-btn.active {
            background: #007bff;
            color: white;
        }

        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        .status-pending { color: #ff9800; }
        .status-completed { color: #4caf50; }
        .status-cancelled { color: #f44336; }
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .header-buttons {
            display: flex;
            gap: 10px;
        }
        .admin-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-card h3 {
            color: #6c757d;
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 600;
            margin: 0;
            color: #2c3e50;
        }

        .stat-number.pending { color: #ffc107; }
        .stat-number.confirmed { color: #28a745; }
        .stat-number.cancelled { color: #dc3545; }

        .admin-table-container {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow-x: auto;
        }

        .admin-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .admin-table th {
            background: #f8f9fa;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
        }

        .admin-table td {
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
            color: #495057;
        }

        .admin-booking-row {
            transition: background-color 0.3s ease;
        }

        .admin-booking-row:hover {
            background-color: #f8f9fa;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .action-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            color: white;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            margin: 0.25rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            background-color: #6c757d; /* Default color */
        }

        .action-btn.accept {
            background-color: #28a745;
        }

        .action-btn.delete {
            background-color: #dc3545;
        }

        .action-btn.confirm {
            background-color: #28a745;
        }

        .action-btn.print {
            background-color: #17a2b8;
        }

        .action-btn.cancel {
            background-color: #dc3545;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            opacity: 0.9;
            box-shadow: 0 4px 6px rgba(0,0,0,0.2);
        }

        .action-btn i {
            font-size: 0.875rem;
        }

        .admin-booking-row.pending { background-color: #fff8e1; }
        .admin-booking-row.confirmed { background-color: #e8f5e9; }
        .admin-booking-row.cancelled { background-color: #ffebee; }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="dashboard-header">
            <h1 style="font-size: 2.5em;">Admin Dashboard</h1>
            <div class="header-buttons">
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <button onclick="showTab('users')" class="tab-btn active">Users</button>
                    <button onclick="showTab('bookings')" class="tab-btn">Bookings</button>
                    <button onclick="showTab('schedules')" class="tab-btn">Schedules</button>
                    <button onclick="showTab('bus-classes')" class="tab-btn">Bus Classes</button>
                    <button onclick="showTab('admin')" class="tab-btn">Admin</button>
                <?php else: ?>
                    <button onclick="showTab('bookings')" class="tab-btn active">My Booking History</button>
                    <button onclick="showTab('history')" class="tab-btn">Booking History</button>
                <?php endif; ?>
                <a href="logout.php" class="btn btn-delete">Logout</a>
            </div>
        </div>

        <!-- Users Tab -->
        <div id="users" class="tab-content section <?php if ($_SESSION['role'] !== 'admin') echo 'none' ?>">
            <h2>Users</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($users)): ?>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['id']) ?></td>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= htmlspecialchars($user['fullname']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars($user['phone']) ?></td>
                            <td><?= htmlspecialchars($user['role']) ?></td>
                            <td class="action-buttons">
                                <a href="edit-user.php?id=<?= $user['id'] ?>" class="btn btn-edit">Edit</a>
                                <button onclick="deleteUser(<?= $user['id'] ?>)" class="btn btn-delete">Delete</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7">No users found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- My Bookings Tab for regular users -->
        <?php if ($_SESSION['role'] !== 'admin'): ?>
        <div id="bookings" class="tab-content section active">
            <h2>My Booking History</h2>
            <div class="booking-filters">
                <button class="filter-btn all active" onclick="filterBookings('all')">All</button>
                <button class="filter-btn pending" onclick="filterBookings('pending')">Pending</button>
                <button class="filter-btn confirmed" onclick="filterBookings('confirmed')">Confirmed</button>
                <button class="filter-btn cancelled" onclick="filterBookings('cancelled')">Cancelled</button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Booking Date</th>
                        <th>Bus Class</th>
                        <th>Departure Time</th>
                        <th>Seats</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Get all bookings for the user ordered by booking date
                    $user_bookings_query = "SELECT b.*, s.departure_time, bc.class_name 
                                          FROM bookings b 
                                          JOIN schedules s ON b.schedule_id = s.id 
                                          JOIN bus_classes bc ON b.bus_class_id = bc.id
                                          WHERE b.user_id = ?
                                          ORDER BY b.booking_date DESC";
                    $user_bookings_stmt = $db->prepare($user_bookings_query);
                    $user_bookings_stmt->execute([$_SESSION['user_id']]);
                    $user_bookings = $user_bookings_stmt->fetchAll(PDO::FETCH_ASSOC);

                    if ($user_bookings):
                        foreach ($user_bookings as $booking):
                            // Get booked seats
                            $seats_query = "SELECT seat_number FROM booked_seats WHERE booking_id = ?";
                            $seats_stmt = $db->prepare($seats_query);
                            $seats_stmt->execute([$booking['id']]);
                            $seats = $seats_stmt->fetchAll(PDO::FETCH_COLUMN);
                    ?>
                        <tr class="booking-row <?= strtolower($booking['payment_status']) ?>">
                            <td><?= date('M d, Y h:i A', strtotime($booking['booking_date'])) ?></td>
                            <td><?= htmlspecialchars($booking['class_name']) ?></td>
                            <td><?= date('M d, Y h:i A', strtotime($booking['departure_time'])) ?></td>
                            <td><?= htmlspecialchars(implode(', ', $seats)) ?></td>
                            <td>₱<?= number_format($booking['total_amount'], 2) ?></td>
                            <td>
                                <span class="status-badge <?= strtolower($booking['payment_status']) ?>">
                                    <?= ucfirst(htmlspecialchars($booking['payment_status'])) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($booking['payment_status'] === 'confirmed'): ?>
                                    <button class="print-btn" onclick="printTicket(<?= $booking['id'] ?>)">Print Ticket</button>
                                <?php endif; ?>
                                <?php if ($booking['payment_status'] === 'pending'): ?>
                                    <button class="action-btn edit" onclick="editBooking(<?= $booking['id'] ?>)" title="Edit Booking">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="action-btn user-cancel" onclick="userCancelBooking(<?= $booking['id'] ?>)" title="Cancel Booking">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php
                        endforeach;
                    else:
                    ?>
                        <tr>
                            <td colspan="7">No bookings found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Admin Bookings Tab -->
        <?php if ($_SESSION['role'] === 'admin'): ?>
        <div id="bookings" class="tab-content section">
            <h2>All Bookings</h2>
            <table>
                <thead>
                    <tr>
                        <th>Booking Date</th>
                        <th>Passenger Name</th>
                        <th>Email</th>
                        <th>Bus Class</th>
                        <th>Departure Time</th>
                        <th>Seats</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($bookings): ?>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td><?= date('M d, Y h:i A', strtotime($booking['booking_date'])) ?></td>
                                <td><?= htmlspecialchars($booking['fullname']) ?></td>
                                <td><?= htmlspecialchars($booking['email']) ?></td>
                                <td><?= htmlspecialchars($booking['class_name']) ?></td>
                                <td><?= date('M d, Y h:i A', strtotime($booking['departure_time'])) ?></td>
                                <td>
                                    <?php
                                    $seats_query = "SELECT seat_number FROM booked_seats WHERE booking_id = ?";
                                    $seats_stmt = $db->prepare($seats_query);
                                    $seats_stmt->execute([$booking['id']]);
                                    $seats = $seats_stmt->fetchAll(PDO::FETCH_COLUMN);
                                    echo htmlspecialchars(implode(', ', $seats));
                                    ?>
                                </td>
                                <td>₱<?= number_format($booking['total_amount'], 2) ?></td>
                                <td>
                                    <span class="status-badge <?= strtolower($booking['payment_status']) ?>">
                                        <?= ucfirst(htmlspecialchars($booking['payment_status'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($booking['payment_status'] !== 'confirmed'): ?>
                                        <button class="action-btn confirm" onclick="confirmBooking(<?= $booking['id'] ?>)" title="Confirm Booking">
                                            <i class="fas fa-check"></i> Confirm
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($booking['payment_status'] === 'confirmed'): ?>
                                        <button class="action-btn print" onclick="printTicket(<?= $booking['id'] ?>)" title="Print Ticket">
                                            <i class="fas fa-print"></i> Print
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($booking['payment_status'] !== 'cancelled'): ?>
                                        <button class="action-btn cancel" onclick="cancelBooking(<?= $booking['id'] ?>)" title="Cancel Booking">
                                            <i class="fas fa-times"></i> Cancel
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9">No bookings found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Booking History Tab -->
        <div id="history" class="tab-content section <?php if ($_SESSION['role'] === 'admin') echo 'none' ?>">
            <h2>Booking History</h2>
            <table>
                <thead>
                    <tr>
                        <th>Booking Date</th>
                        <th>Bus Class</th>
                        <th>Departure Time</th>
                        <th>Seats</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Get all bookings for the user ordered by booking date
                    $history_query = "SELECT b.*, s.departure_time, bc.class_name 
                                    FROM bookings b 
                                    JOIN schedules s ON b.schedule_id = s.id 
                                    JOIN bus_classes bc ON b.bus_class_id = bc.id
                                    WHERE b.user_id = ?
                                    ORDER BY b.booking_date DESC";
                    $history_stmt = $db->prepare($history_query);
                    $history_stmt->execute([$_SESSION['user_id']]);
                    $history = $history_stmt->fetchAll(PDO::FETCH_ASSOC);

                    if ($history):
                        foreach ($history as $booking):
                            // Get booked seats
                            $seats_query = "SELECT seat_number FROM booked_seats WHERE booking_id = ?";
                            $seats_stmt = $db->prepare($seats_query);
                            $seats_stmt->execute([$booking['id']]);
                            $seats = $seats_stmt->fetchAll(PDO::FETCH_COLUMN);
                    ?>
                        <tr>
                            <td><?= date('M d, Y h:i A', strtotime($booking['booking_date'])) ?></td>
                            <td><?= htmlspecialchars($booking['class_name']) ?></td>
                            <td><?= date('M d, Y h:i A', strtotime($booking['departure_time'])) ?></td>
                            <td><?= htmlspecialchars(implode(', ', $seats)) ?></td>
                            <td>₱<?= number_format($booking['total_amount'], 2) ?></td>
                            <td>
                                <span class="status-badge <?= strtolower($booking['payment_status']) ?>">
                                    <?= ucfirst(htmlspecialchars($booking['payment_status'])) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($booking['payment_status'] === 'confirmed'): ?>
                                    <button class="print-btn" onclick="printTicket(<?= $booking['id'] ?>)">Print Ticket</button>
                                <?php endif; ?>
                                <?php if ($booking['payment_status'] === 'pending'): ?>
                                    <button class="action-btn edit" onclick="editBooking(<?= $booking['id'] ?>)" title="Edit Booking">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="action-btn user-cancel" onclick="userCancelBooking(<?= $booking['id'] ?>)" title="Cancel Booking">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php
                        endforeach;
                    else:
                    ?>
                        <tr>
                            <td colspan="7">No booking history found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Schedules Tab -->
        <div id="schedules" class="tab-content section <?php if ($_SESSION['role'] !== 'admin') echo 'none' ?>">
            <h2>Schedules</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Departure Time</th>
                        <th>Bus Class</th>
                        <th>Available Seats</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($schedules)): ?>
                        <?php foreach ($schedules as $schedule): ?>
                        <tr>
                            <td><?= htmlspecialchars($schedule['id']) ?></td>
                            <td><?= date('h:i A', strtotime($schedule['departure_time'])) ?></td>
                            <td><?= htmlspecialchars($schedule['class_name']) ?></td>
                            <td><?= htmlspecialchars($schedule['available_seats']) ?></td>
                            <td class="action-buttons">
                                <a href="edit-schedule.php?id=<?= $schedule['id'] ?>" class="btn btn-edit">Edit</a>
                                <button onclick="deleteSchedule(<?= $schedule['id'] ?>)" class="btn btn-delete">Delete</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5">No schedules found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Bus Classes Tab -->
        <div id="bus-classes" class="tab-content section <?php if ($_SESSION['role'] !== 'admin') echo 'none' ?>">
            <h2>Bus Classes</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Class Name</th>
                        <th>Price</th>
                        <th>Total Seats</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($bus_classes)): ?>
                        <?php foreach ($bus_classes as $class): ?>
                        <tr>
                            <td><?= htmlspecialchars($class['id']) ?></td>
                            <td><?= htmlspecialchars($class['class_name']) ?></td>
                            <td>₱<?= number_format($class['price'], 2) ?></td>
                            <td><?= htmlspecialchars($class['total_seats']) ?></td>
                            <td class="action-buttons">
                                <a href="edit-class.php?id=<?= $class['id'] ?>" class="btn btn-edit">Edit</a>
                                <button onclick="deleteBusClass(<?= $class['id'] ?>)" class="btn btn-delete">Delete</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5">No bus classes found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Admin Dashboard -->
        <?php if ($_SESSION['role'] === 'admin'): ?>
        <div id="admin" class="tab-content section active">
            <div class="admin-header">
                <h2>Booking Management</h2>
                <div class="admin-filters">
                    <button class="admin-filter-btn all active" onclick="filterAdminBookings('all')">All Bookings</button>
                    <button class="admin-filter-btn pending" onclick="filterAdminBookings('pending')">Pending</button>
                    <button class="admin-filter-btn confirmed" onclick="filterAdminBookings('confirmed')">Confirmed</button>
                    <button class="admin-filter-btn cancelled" onclick="filterAdminBookings('cancelled')">Cancelled</button>
                </div>
            </div>
            
            <div class="admin-stats">
                <div class="stat-card">
                    <h3>Total Bookings</h3>
                    <p class="stat-number" id="totalBookings">0</p>
                    <small class="stat-label">All time bookings</small>
                </div>
                <div class="stat-card">
                    <h3>Pending</h3>
                    <p class="stat-number pending" id="pendingBookings">0</p>
                    <small class="stat-label">Awaiting confirmation</small>
                </div>
                <div class="stat-card">
                    <h3>Confirmed</h3>
                    <p class="stat-number confirmed" id="confirmedBookings">0</p>
                    <small class="stat-label">Successfully confirmed</small>
                </div>
                <div class="stat-card">
                    <h3>Cancelled</h3>
                    <p class="stat-number cancelled" id="cancelledBookings">0</p>
                    <small class="stat-label">Cancelled bookings</small>
                </div>
            </div>

            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Passenger</th>
                            <th>Contact</th>
                            <th>Bus Class</th>
                            <th>Departure</th>
                            <th>Seats</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="adminBookingsTable">
                    <?php
                        $admin_bookings_query = "SELECT b.*, u.email, u.fullname, s.departure_time, bc.class_name 
                                               FROM bookings b 
                                               JOIN users u ON b.user_id = u.id 
                                               JOIN schedules s ON b.schedule_id = s.id 
                                               JOIN bus_classes bc ON b.bus_class_id = bc.id 
                                               ORDER BY b.booking_date DESC";
                        $admin_bookings_stmt = $db->prepare($admin_bookings_query);
                        $admin_bookings_stmt->execute();
                        $admin_bookings = $admin_bookings_stmt->fetchAll(PDO::FETCH_ASSOC);

                        $total = count($admin_bookings);
                        $pending = $confirmed = $cancelled = 0;

                        foreach ($admin_bookings as $booking):
                            // Update counters
                            switch($booking['payment_status']) {
                                case 'pending': $pending++; break;
                                case 'confirmed': $confirmed++; break;
                                case 'cancelled': $cancelled++; break;
                            }

                            // Get booked seats
                            $seats_query = "SELECT seat_number FROM booked_seats WHERE booking_id = ?";
                            $seats_stmt = $db->prepare($seats_query);
                            $seats_stmt->execute([$booking['id']]);
                            $seats = $seats_stmt->fetchAll(PDO::FETCH_COLUMN);
                    ?>
                        <tr class="admin-booking-row <?= strtolower($booking['payment_status']) ?>" data-booking-id="<?= $booking['id'] ?>">
                            <td>#<?= str_pad($booking['id'], 5, '0', STR_PAD_LEFT) ?></td>
                            <td><?= htmlspecialchars($booking['fullname']) ?></td>
                            <td><?= htmlspecialchars($booking['email']) ?></td>
                            <td><?= htmlspecialchars($booking['class_name']) ?></td>
                            <td><?= date('M d, Y h:i A', strtotime($booking['departure_time'])) ?></td>
                            <td><?= htmlspecialchars(implode(', ', $seats)) ?></td>
                            <td>₱<?= number_format($booking['total_amount'], 2) ?></td>
                            <td>
                                <span class="status-badge <?= strtolower($booking['payment_status']) ?>">
                                    <?= ucfirst(htmlspecialchars($booking['payment_status'])) ?>
                                </span>
                            </td>
                            <td class="action-buttons">
                                <?php if ($booking['payment_status'] === 'pending'): ?>
                                    <button class="action-btn confirm" onclick="confirmBooking(<?= $booking['id'] ?>)" title="Confirm Booking">
                                        <i class="fas fa-check"></i> Confirm
                                    </button>
                                <?php endif; ?>
                                <?php if ($booking['payment_status'] === 'confirmed'): ?>
                                    <button class="action-btn print" onclick="printTicket(<?= $booking['id'] ?>)" title="Print Ticket">
                                        <i class="fas fa-print"></i> Print
                                    </button>
                                <?php endif; ?>
                                <?php if ($booking['payment_status'] !== 'cancelled'): ?>
                                    <button class="action-btn cancel" onclick="cancelBooking(<?= $booking['id'] ?>)" title="Cancel Booking">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <script>
            function showTab(tabId) {
                // Hide all tabs
                document.querySelectorAll('.tab-content').forEach(tab => {
                    tab.style.display = 'none';
                });
                
                // Remove active class from all buttons
                document.querySelectorAll('.tab-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                
                // Show selected tab
                document.getElementById(tabId).style.display = 'block';
                
                // Add active class to clicked button
                document.querySelector(`button[onclick="showTab('${tabId}')"]`).classList.add('active');
            }

            // Show initial tab based on user role
            document.addEventListener('DOMContentLoaded', function() {
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    showTab('users');
                <?php else: ?>
                    showTab('bookings');
                <?php endif; ?>
            });

            function acceptBooking(bookingId) {
                if (confirm('Are you sure you want to confirm this booking?')) {
                    window.location.href = `accept-booking.php?id=${bookingId}`;
                }
            }

            function deleteBooking(bookingId) {
                if (confirm('Are you sure you want to delete this booking?')) {
                    window.location.href = `delete-booking.php?id=${bookingId}`;
                }
            }

            function printTicket(bookingId) {
                window.open(`print-ticket.php?id=${bookingId}`, '_blank');
            }

            function filterBookings(status) {
                // Update active filter button
                document.querySelectorAll('.filter-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                document.querySelector(`.filter-btn.${status}`).classList.add('active');

                // Filter booking rows
                document.querySelectorAll('.booking-row').forEach(row => {
                    if (status === 'all' || row.classList.contains(status)) {
                        row.classList.remove('hidden');
                    } else {
                        row.classList.add('hidden');
                    }
                });
            }

            function cancelBooking(bookingId) {
                if (confirm('Are you sure you want to cancel this booking?')) {
                    // Update UI immediately
                    const row = document.querySelector(`tr[data-booking-id="${bookingId}"]`);
                    if (row) {
                        const currentStatus = row.classList.contains('pending') ? 'pending' : 'confirmed';
                        row.classList.remove(currentStatus);
                        row.classList.add('cancelled');
                        
                        // Update status badge
                        const statusBadge = row.querySelector('.status-badge');
                        if (statusBadge) {
                            statusBadge.className = 'status-badge cancelled';
                            statusBadge.textContent = 'Cancelled';
                        }
                        
                        // Update action buttons
                        const actionButtons = row.querySelector('.action-buttons');
                        if (actionButtons) {
                            actionButtons.innerHTML = ''; // Remove all action buttons
                        }

                        // Update statistics
                        const cancelledCount = document.getElementById('cancelledBookings');
                        if (cancelledCount) {
                            cancelledCount.textContent = parseInt(cancelledCount.textContent) + 1;
                        }
                        
                        if (currentStatus === 'pending') {
                            const pendingCount = document.getElementById('pendingBookings');
                            if (pendingCount) {
                                pendingCount.textContent = parseInt(pendingCount.textContent) - 1;
                            }
                        } else {
                            const confirmedCount = document.getElementById('confirmedBookings');
                            if (confirmedCount) {
                                confirmedCount.textContent = parseInt(confirmedCount.textContent) - 1;
                            }
                        }
                    }

                    // Send request to server
                    window.location.href = `cancel-booking.php?id=${bookingId}`;
                }
            }

            function confirmBooking(bookingId) {
                if (confirm('Are you sure you want to confirm this booking?')) {
                    // Update UI immediately
                    const row = document.querySelector(`tr[data-booking-id="${bookingId}"]`);
                    if (row) {
                        const currentStatus = row.classList.contains('pending') ? 'pending' : '';
                        row.classList.remove(currentStatus);
                        row.classList.add('confirmed');
                        
                        // Update status badge
                        const statusBadge = row.querySelector('.status-badge');
                        if (statusBadge) {
                            statusBadge.className = 'status-badge confirmed';
                            statusBadge.textContent = 'Confirmed';
                        }
                        
                        // Update action buttons
                        const actionButtons = row.querySelector('.action-buttons');
                        if (actionButtons) {
                            actionButtons.innerHTML = `
                                <button class="action-btn print" onclick="printTicket(${bookingId})" title="Print Ticket">
                                    <i class="fas fa-print"></i> Print
                                </button>
                                <button class="action-btn cancel" onclick="cancelBooking(${bookingId})" title="Cancel Booking">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                            `;
                        }

                        // Update statistics
                        if (currentStatus === 'pending') {
                            const pendingCount = document.getElementById('pendingBookings');
                            const confirmedCount = document.getElementById('confirmedBookings');
                            if (pendingCount && confirmedCount) {
                                pendingCount.textContent = parseInt(pendingCount.textContent) - 1;
                                confirmedCount.textContent = parseInt(confirmedCount.textContent) + 1;
                            }
                        }
                    }

                    // Send request to server
                    window.location.href = `accept-booking.php?id=${bookingId}`;
                }
            }

            function filterAdminBookings(status) {
                // Update active filter button
                document.querySelectorAll('.admin-filter-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                document.querySelector(`.admin-filter-btn.${status}`).classList.add('active');

                // Filter booking rows with animation
                document.querySelectorAll('.admin-booking-row').forEach(row => {
                    if (status === 'all' || row.classList.contains(status)) {
                        row.style.display = '';
                        setTimeout(() => row.style.opacity = '1', 10);
                    } else {
                        row.style.opacity = '0';
                        setTimeout(() => row.style.display = 'none', 300);
                    }
                });
            }

            function editBooking(bookingId) {
                window.location.href = `edit-booking.php?id=${bookingId}`;
            }

            function userCancelBooking(bookingId) {
                if (confirm('Are you sure you want to cancel this booking?')) {
                    // Update UI immediately
                    const row = document.querySelector(`tr[data-booking-id="${bookingId}"]`);
                    if (row) {
                        row.classList.remove('pending');
                        row.classList.add('cancelled');
                        
                        // Update status badge
                        const statusBadge = row.querySelector('.status-badge');
                        if (statusBadge) {
                            statusBadge.className = 'status-badge cancelled';
                            statusBadge.textContent = 'Cancelled';
                        }
                        
                        // Remove action buttons
                        const actionButtons = row.querySelector('.action-buttons');
                        if (actionButtons) {
                            actionButtons.innerHTML = '';
                        }
                    }

                    // Send request to server
                    window.location.href = `cancel-booking.php?id=${bookingId}`;
                }
            }

            // Initialize statistics on page load
            document.addEventListener('DOMContentLoaded', function() {
                if (document.getElementById('totalBookings')) {
                    document.getElementById('totalBookings').textContent = '<?= $total ?>';
                    document.getElementById('pendingBookings').textContent = '<?= $pending ?>';
                    document.getElementById('confirmedBookings').textContent = '<?= $confirmed ?>';
                    document.getElementById('cancelledBookings').textContent = '<?= $cancelled ?>';
                }
            });
        </script>
    </div>

    <style>
        .admin-filter-btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f8f9fa;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .admin-filter-btn.active {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.15);
        }

        .admin-filter-btn.all.active { background: #2c3e50; }
        .admin-filter-btn.pending.active { background: #f39c12; }
        .admin-filter-btn.confirmed.active { background: #27ae60; }
        .admin-filter-btn.cancelled.active { background: #c0392b; }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            text-align: center;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }

        .stat-label {
            display: block;
            color: #95a5a6;
            margin-top: 0.5rem;
            font-size: 0.875rem;
        }

        .action-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            color: white;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            margin: 0.25rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            background-color: #6c757d; /* Default color */
        }

        .action-btn.accept {
            background-color: #28a745;
        }

        .action-btn.delete {
            background-color: #dc3545;
        }

        .action-btn.confirm {
            background-color: #28a745;
        }

        .action-btn.print {
            background-color: #17a2b8;
        }

        .action-btn.cancel {
            background-color: #dc3545;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            opacity: 0.9;
            box-shadow: 0 4px 6px rgba(0,0,0,0.2);
        }

        .action-btn i {
            font-size: 0.875rem;
        }

        .admin-booking-row.pending { background-color: #fff8e1; }
        .admin-booking-row.confirmed { background-color: #e8f5e9; }
        .admin-booking-row.cancelled { background-color: #ffebee; }
    </style>
</body>
</html>

<?php
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );

    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
?>
