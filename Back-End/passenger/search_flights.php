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
    SELECT DISTINCT f.id, f.flight_code, f.name, f.fees, f.start_datetime, f.end_datetime
    FROM flights f
    JOIN flight_itinerary i1 ON f.id = i1.flight_id
    JOIN flight_itinerary i2 ON f.id = i2.flight_id
    WHERE i1.city_name = ?
    AND i2.city_name = ?
    AND f.is_completed = 0
");
$stmt->bind_param("ss", $from, $to);
$stmt->execute();
$flights = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

jsonResponse(true, "Available flights", $flights);
