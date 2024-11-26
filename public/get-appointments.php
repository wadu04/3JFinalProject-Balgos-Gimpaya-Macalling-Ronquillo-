<?php
require_once '../app/config/database.php';
header('Content-Type: application/json');

// Fetch all appointments
$stmt = $conn->prepare("
    SELECT 
        a.id,
        a.start_time,
        a.end_time,
        s.service_name,
        u.fullname as therapist_name,
        a.status
    FROM appointments a
    JOIN services s ON a.service_id = s.id
    JOIN users u ON a.therapist_id = u.id
    WHERE a.status != 'canceled'
");
$stmt->execute();
$result = $stmt->get_result();

$events = [];
while ($row = $result->fetch_assoc()) {
    $events[] = [
        'id' => $row['id'],
        'title' => $row['service_name'] . ' with ' . $row['therapist_name'],
        'start' => $row['start_time'],
        'end' => $row['end_time'],
        'backgroundColor' => $row['status'] === 'completed' ? '#28a745' : '#007bff',
        'borderColor' => $row['status'] === 'completed' ? '#28a745' : '#007bff'
    ];
}

echo json_encode($events);
