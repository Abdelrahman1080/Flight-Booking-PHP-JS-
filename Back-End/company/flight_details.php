<?php
session_start();
header("Content-Type: application/json");
require "../configration/db.php";
require "../configration/helper-functions.php";

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'company') {
    jsonResponse(false, "Unauthorized");
}

$flightId = $_GET['flight_id'] ?? 0;
if (!$flightId) jsonResponse(false, "Flight ID required");

$stmt = $conn->prepare("SELECT id FROM companies WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$company = $stmt->get_result()->fetch_assoc();

$stmt = $conn->prepare("
    SELECT id, name, flight_code, fees, max_passengers, start_datetime, end_datetime, is_completed
    FROM flights
    WHERE id = ? AND company_id = ?
");
$stmt->bind_param("ii", $flightId, $company['id']);
$stmt->execute();
$flight = $stmt->get_result()->fetch_assoc();
if (!$flight) jsonResponse(false, "Flight not found");

$stmt = $conn->prepare("
    SELECT city_name, city_order 
    FROM flight_itinerary 
    WHERE flight_id = ? 
    ORDER BY city_order
");
$stmt->bind_param("i", $flightId);
$stmt->execute();
$itinerary = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt = $conn->prepare("
    SELECT fb.id AS booking_id, p.id AS passenger_id, u.id AS user_id, u.name, u.email, p.account_balance
    FROM flight_bookings fb
    JOIN passengers p ON fb.passenger_id = p.id
    JOIN users u ON p.user_id = u.id
    WHERE fb.flight_id = ? AND fb.status = 'pending'
");
$stmt->bind_param("i", $flightId);
$stmt->execute();
$pending = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt = $conn->prepare("
    SELECT fb.id AS booking_id, p.id AS passenger_id, u.id AS user_id, u.name, u.email, p.account_balance
    FROM flight_bookings fb
    JOIN passengers p ON fb.passenger_id = p.id
    JOIN users u ON p.user_id = u.id
    WHERE fb.flight_id = ? AND fb.status = 'registered'
");
$stmt->bind_param("i", $flightId);
$stmt->execute();
$registered = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

jsonResponse(true, "Flight details loaded", [
    "flight" => $flight,
    "itinerary" => $itinerary,
    "pending_passengers" => $pending,
    "registered_passengers" => $registered
]);
