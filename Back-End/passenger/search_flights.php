<?php
session_start();
header("Content-Type: application/json");

require "../configration/db.php";
require "../configration/helper-functions.php";

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'passenger') {
    jsonResponse(false, "Unauthorized");
}

$from = $_GET['from'] ?? '';
$to   = $_GET['to'] ?? '';

if (!$from || !$to) {
    jsonResponse(false, "From and To are required");
}

$stmt = $conn->prepare("
    SELECT DISTINCT f.id, f.flight_code, f.name, f.fees, f.start_datetime, f.end_datetime, f.max_passengers,
           COUNT(CASE WHEN fb.status = 'registered' THEN 1 END) as booked_seats
    FROM flights f
    JOIN flight_itinerary i1 ON f.id = i1.flight_id
    JOIN flight_itinerary i2 ON f.id = i2.flight_id
    LEFT JOIN flight_bookings fb ON f.id = fb.flight_id
    WHERE i1.city_name = ?
    AND i2.city_name = ?
    AND f.is_completed = 0
    GROUP BY f.id
    HAVING booked_seats < f.max_passengers
    -- NOTE: We only count 'registered' bookings. Cancelled/pending bookings don't reduce available seats
    -- When a passenger cancels (status='cancelled'), the seat is automatically freed
");
$stmt->bind_param("ss", $from, $to);
$stmt->execute();
$result = $stmt->get_result();
$flights = [];
while ($row = $result->fetch_assoc()) {
    $row['remaining_seats'] = (int)$row['max_passengers'] - (int)$row['booked_seats'];
    $flights[] = $row;
}

jsonResponse(true, "Available flights", $flights);
