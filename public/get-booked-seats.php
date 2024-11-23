<?php
session_start();
require_once '../app/config/database.php';

header('Content-Type: application/json');
$response = array('booked_seats' => array());

if (!isset($_GET['bus_class_id']) || !isset($_GET['schedule'])) {
    echo json_encode($response);
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    // Get schedule ID
    $schedule_query = "SELECT id FROM schedules 
                      WHERE bus_class_id = ? AND departure_time = ?";
    $schedule_stmt = $db->prepare($schedule_query);
    $schedule_stmt->execute([$_GET['bus_class_id'], $_GET['schedule']]);
    $schedule = $schedule_stmt->fetch(PDO::FETCH_ASSOC);

    if ($schedule) {
        // Get booked seats for this schedule
        $seats_query = "SELECT bs.seat_number 
                       FROM booked_seats bs
                       JOIN bookings b ON bs.booking_id = b.id
                       WHERE b.schedule_id = ?";
        $seats_stmt = $db->prepare($seats_query);
        $seats_stmt->execute([$schedule['id']]);
        
        while ($seat = $seats_stmt->fetch(PDO::FETCH_ASSOC)) {
            $response['booked_seats'][] = (int)$seat['seat_number'];
        }
    }
} catch (PDOException $e) {
    $response['error'] = 'Database error: ' . $e->getMessage();
}

echo json_encode($response);
