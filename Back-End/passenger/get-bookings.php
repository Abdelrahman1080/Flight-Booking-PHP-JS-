<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../configration/db.php';
require_once __DIR__ . '/../configration/helper-functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'passenger') {
    jsonResponse(false, 'Unauthorized');
}

$userId = $_SESSION['user_id'];

// Get passenger ID
$passengerStmt = $conn->prepare('SELECT id FROM passengers WHERE user_id = ? LIMIT 1');
$passengerStmt->bind_param('i', $userId);
$passengerStmt->execute();
$passenger = $passengerStmt->get_result()->fetch_assoc();
$passengerStmt->close();

if (!$passenger) {
    jsonResponse(false, 'Passenger profile not found');
}

$passengerId = (int)$passenger['id'];

$sql = "SELECT 
    fb.id as booking_id,
    fb.status,
    fb.booked_at,
    f.id as flight_id,
    f.flight_code,
    f.name as flight_name,
    f.fees,
    f.start_datetime,
    f.end_datetime,
    f.is_completed,
    c.comapny_name as company_name
FROM flight_bookings fb
JOIN flights f ON fb.flight_id = f.id
LEFT JOIN companies c ON f.company_id = c.id
WHERE fb.passenger_id = ?
ORDER BY fb.booked_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $passengerId);
$stmt->execute();
$result = $stmt->get_result();

$bookings = [];
while ($row = $result->fetch_assoc()) {
    $itineraryStmt = $conn->prepare('SELECT city_name FROM flight_itinerary WHERE flight_id = ? ORDER BY city_order');
    $itineraryStmt->bind_param('i', $row['flight_id']);
    $itineraryStmt->execute();
    $itineraryRes = $itineraryStmt->get_result();
    $cities = [];
    while ($city = $itineraryRes->fetch_assoc()) {
        $cities[] = $city['city_name'];
    }
    $itineraryStmt->close();
    
    $bookings[] = [
        'booking_id' => $row['booking_id'],
        'flight_id' => $row['flight_id'],
        'flight_code' => $row['flight_code'],
        'flight_name' => $row['flight_name'],
        'company_name' => $row['company_name'] ?: 'Unknown Airline',
        'status' => $row['status'],
        'fees' => $row['fees'],
        'start_datetime' => $row['start_datetime'],
        'end_datetime' => $row['end_datetime'],
        'is_completed' => (int)$row['is_completed'],
        'booked_at' => $row['booked_at'],
        'route' => implode(' → ', $cities),
        'origin' => $cities[0] ?? '',
        'destination' => $cities[count($cities)-1] ?? ''
    ];
}
$stmt->close();

jsonResponse(true, 'Bookings loaded', $bookings);
