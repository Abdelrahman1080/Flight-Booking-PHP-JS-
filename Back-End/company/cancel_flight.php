<?php
session_start();
header("Content-Type: application/json");
require "../configration/db.php";
require "../configration/helper-functions.php";

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'company') {
    jsonResponse(false, "Unauthorized");
}

$flightId = $_POST['flight_id'] ?? 0;
if (!$flightId) jsonResponse(false, "Flight ID required");

$stmt = $conn->prepare("SELECT id FROM companies WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$company = $stmt->get_result()->fetch_assoc();

$stmt = $conn->prepare("SELECT fees FROM flights WHERE id = ? AND company_id = ?");
$stmt->bind_param("ii", $flightId, $company['id']);
$stmt->execute();
$flight = $stmt->get_result()->fetch_assoc();
if (!$flight) jsonResponse(false, "Flight not found");

$conn->begin_transaction();
try {
    $stmt = $conn->prepare("
        SELECT p.id 
        FROM flight_bookings fb
        JOIN passengers p ON fb.passenger_id = p.id
        WHERE fb.flight_id = ? AND fb.status = 'registered'
    ");
    $stmt->bind_param("i", $flightId);
    $stmt->execute();
    $registered = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    foreach ($registered as $passenger) {
        $stmt = $conn->prepare("UPDATE passengers SET account_balance = account_balance + ? WHERE id = ?");
        $stmt->bind_param("di", $flight['fees'], $passenger['id']);
        $stmt->execute();
    }

    $stmt = $conn->prepare("UPDATE flights SET is_completed = 1 WHERE id = ?");
    $stmt->bind_param("i", $flightId);
    $stmt->execute();

    $stmt = $conn->prepare("DELETE FROM flight_bookings WHERE flight_id = ?");
    $stmt->bind_param("i", $flightId);
    $stmt->execute();

    $conn->commit();
    jsonResponse(true, "Flight cancelled and refunds processed");
} catch (Exception $e) {
    $conn->rollback();
    jsonResponse(false, "Failed to cancel flight: " . $e->getMessage());
}
