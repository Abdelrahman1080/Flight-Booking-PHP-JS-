<?php
session_start();
header("Content-Type: application/json");

require "../configration/db.php";
require "../configration/helper-functions.php";

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'company') {
    jsonResponse(false, "Unauthorized");
}

// Enforce strict error reporting for clearer failures
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$payload = json_decode(file_get_contents("php://input"), true);
if (!is_array($payload)) {
    jsonResponse(false, "Invalid JSON payload");
}

$flightCode     = trim($payload['flight_code'] ?? '');
$name           = trim($payload['name'] ?? '');
$fees           = isset($payload['fees']) ? floatval($payload['fees']) : 0;
$maxPassengers  = isset($payload['max_passengers']) ? intval($payload['max_passengers']) : 0;
$start          = trim($payload['start_datetime'] ?? '');
$end            = trim($payload['end_datetime'] ?? '');
$itinerary      = is_array($payload['itinerary'] ?? null) ? $payload['itinerary'] : [];

if (!$flightCode || !$name || !$start || !$end) {
    jsonResponse(false, "Missing flight data");
}

// Ensure company exists for current session
$stmt = $conn->prepare("SELECT id FROM companies WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$company = $stmt->get_result()->fetch_assoc();
if (!$company) {
    jsonResponse(false, "Company profile not found");
}

$conn->begin_transaction();

try {
    // Insert flight
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

    // Insert itinerary stops (if any)
    foreach ($itinerary as $index => $city) {
        $cityName = trim((string)$city);
        if ($cityName === '') {
            continue;
        }
        $order = $index + 1;
        $stmt = $conn->prepare("
            INSERT INTO flight_itinerary (flight_id, city_name, city_order)
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param("isi", $flightId, $cityName, $order);
        $stmt->execute();
    }

    $conn->commit();
    jsonResponse(true, "Flight added successfully", ["flight_id" => $flightId]);

} catch (Exception $e) {
    $conn->rollback();
    jsonResponse(false, "Failed to add flight");
}
