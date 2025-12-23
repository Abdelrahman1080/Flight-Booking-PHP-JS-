<?php
session_start();
header("Content-Type: application/json");

require "../configration/db.php";
require "../configration/helper-functions.php";

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'company') {
    jsonResponse(false, "Unauthorized");
}

$action = $_POST['action'] ?? '';
$bookingId = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;

if (!$action || !$bookingId) {
    jsonResponse(false, "Missing action or booking_id");
}

if (!in_array($action, ['confirm', 'decline'])) {
    jsonResponse(false, "Invalid action");
}

// Get booking details
$bookingStmt = $conn->prepare("
    SELECT fb.id, fb.flight_id, fb.passenger_id, fb.status, f.company_id
    FROM flight_bookings fb
    JOIN flights f ON fb.flight_id = f.id
    WHERE fb.id = ?
");
$bookingStmt->bind_param("i", $bookingId);
$bookingStmt->execute();
$booking = $bookingStmt->get_result()->fetch_assoc();
$bookingStmt->close();

if (!$booking) {
    jsonResponse(false, "Booking not found");
}

// Verify company owns this flight
$companyStmt = $conn->prepare("SELECT id FROM companies WHERE user_id = ?");
$companyStmt->bind_param("i", $_SESSION['user_id']);
$companyStmt->execute();
$company = $companyStmt->get_result()->fetch_assoc();
$companyStmt->close();

if (!$company || $company['id'] != $booking['company_id']) {
    jsonResponse(false, "Unauthorized");
}

// Check if booking is already processed
if ($booking['status'] !== 'pending') {
    jsonResponse(false, "Booking is already " . $booking['status']);
}

$newStatus = ($action === 'confirm') ? 'registered' : 'cancelled';

// If declining, refund the passenger
if ($action === 'decline') {
    // Get flight fee and passenger balance
    $flightStmt = $conn->prepare("SELECT fees FROM flights WHERE id = ?");
    $flightStmt->bind_param("i", $booking['flight_id']);
    $flightStmt->execute();
    $flight = $flightStmt->get_result()->fetch_assoc();
    $flightStmt->close();

    $fee = (float)($flight['fees'] ?? 0);

    // Refund passenger
    $refundStmt = $conn->prepare("
        UPDATE passengers
        SET account_balance = account_balance + ?
        WHERE id = ?
    ");
    $refundStmt->bind_param("di", $fee, $booking['passenger_id']);
    $refundStmt->execute();
    $refundStmt->close();
}

// Update booking status
$updateStmt = $conn->prepare("UPDATE flight_bookings SET status = ? WHERE id = ?");
$updateStmt->bind_param("si", $newStatus, $bookingId);
$updateStmt->execute();
$updateStmt->close();

$message = ($action === 'confirm') ? "Booking confirmed" : "Booking declined and passenger refunded";
jsonResponse(true, $message);
?>
