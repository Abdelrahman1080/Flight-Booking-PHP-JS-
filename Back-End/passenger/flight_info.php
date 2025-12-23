<?php
session_start();
header("Content-Type: application/json");

require "../configration/db.php";
require "../configration/helper-functions.php";

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'passenger') {
    jsonResponse(false, "Unauthorized");
}

$flight_id = $_GET['flight_id'] ?? '';

if (!$flight_id) {
    jsonResponse(false, "Flight ID is required");
}

$stmt = $conn->prepare("
    SELECT 
        f.id,
        f.flight_code,
        f.name,
        f.fees,
        f.max_passengers,
        f.start_datetime,
        f.end_datetime,
        f.is_completed,
        u.name AS company_name,
        c.logo AS company_logo
    FROM flights f
    JOIN companies c ON f.company_id = c.id
    JOIN users u ON c.user_id = u.id
    WHERE f.id = ?
");
$stmt->bind_param("i", $flight_id);
$stmt->execute();
$flight = $stmt->get_result()->fetch_assoc();

if (!$flight) {
    jsonResponse(false, "Flight not found");
}


$stmt = $conn->prepare("
    SELECT city_name, city_order
    FROM flight_itinerary
    WHERE flight_id = ?
    ORDER BY city_order
");
$stmt->bind_param("i", $flight_id);
$stmt->execute();
$itinerary = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);


$stmt = $conn->prepare("
    SELECT 
        SUM(status = 'registered') AS registered,
        SUM(status = 'pending') AS pending
    FROM flight_bookings
    WHERE flight_id = ?
");
$stmt->bind_param("i", $flight_id);
$stmt->execute();
$counts = $stmt->get_result()->fetch_assoc();

jsonResponse(true, "Flight info loaded", [
    "flight" => $flight,
    "itinerary" => $itinerary,
    "passengers" => $counts
]);
