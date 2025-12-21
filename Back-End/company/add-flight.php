<?php
session_start();
header("Content-Type: application/json");

require "../configration/db.php";
require "../configration/helper-functions.php";

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'company') {
    jsonResponse(false, "Unauthorized");
}

$data = json_decode(file_get_contents("php://input"), true);

$flightCode = $data['flight_code'] ?? '';
$name = $data['name'] ?? '';
$fees = $data['fees'] ?? 0;
$maxPassengers = $data['max_passengers'] ?? 0;
$start = $data['start_datetime'] ?? '';
$end = $data['end_datetime'] ?? '';
$itinerary = $data['itinerary'] ?? [];

if (!$flightCode || !$name || !$start || !$end) {
    jsonResponse(false, "Missing flight data");
}

/* get company id */
$stmt = $conn->prepare("SELECT id FROM companies WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$company = $stmt->get_result()->fetch_assoc();

$conn->begin_transaction();

try {
    /* insert flight */
    $stmt = $conn->prepare("
        INSERT INTO flights (company_id, flight_code, name, fees, max_passengers, start_datetime, end_datetime)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        "issdiss",
        $company['id'],
        $flightCode,
        $name,
        $fees,
        $maxPassengers,
        $start,
        $end
    );
    $stmt->execute();
    $flightId = $stmt->insert_id;

    /* itinerary */
    foreach ($itinerary as $index => $city) {
        $stmt = $conn->prepare("
            INSERT INTO flight_itinerary (flight_id, city_name, city_order)
            VALUES (?, ?, ?)
        ");
        $order = $index + 1;
        $stmt->bind_param("isi", $flightId, $city, $order);
        $stmt->execute();
    }

    $conn->commit();
    jsonResponse(true, "Flight added successfully");

} catch (Exception $e) {
    $conn->rollback();
    jsonResponse(false, "Failed to add flight");
}
