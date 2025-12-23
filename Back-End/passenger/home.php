<?php
session_start();
header("Content-Type: application/json");

require "../configration/db.php";
require "../configration/helper-functions.php";

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'passenger') {
    jsonResponse(false, "Unauthorized");
}

$user_id = $_SESSION['user_id'];

/* =========================
   Passenger Info
========================= */
$stmt = $conn->prepare("
    SELECT 
        p.id AS passenger_id,
        u.name,
        u.email,
        u.tel,
        p.photo
    FROM passengers p
    JOIN users u ON p.user_id = u.id
    WHERE u.id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$passenger = $stmt->get_result()->fetch_assoc();

if (!$passenger) {
    jsonResponse(false, "Passenger not found");
}

/* =========================
   Current Flights
========================= */
$stmt = $conn->prepare("
    SELECT f.id, f.flight_code, f.name, f.start_datetime, f.end_datetime
    FROM flight_bookings fb
    JOIN flights f ON fb.flight_id = f.id
    WHERE fb.passenger_id = ?
    AND fb.status IN ('pending', 'registered')
");
$stmt->bind_param("i", $passenger['passenger_id']);
$stmt->execute();
$current_flights = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

/* =========================
   Completed Flights
========================= */
$stmt = $conn->prepare("
    SELECT f.id, f.flight_code, f.name, f.start_datetime, f.end_datetime
    FROM flight_bookings fb
    JOIN flights f ON fb.flight_id = f.id
    WHERE fb.passenger_id = ?
    AND f.is_completed = 1
");
$stmt->bind_param("i", $passenger['passenger_id']);
$stmt->execute();
$completed_flights = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

jsonResponse(true, "Passenger home loaded", [
    "passenger" => $passenger,
    "current_flights" => $current_flights,
    "completed_flights" => $completed_flights
]);
