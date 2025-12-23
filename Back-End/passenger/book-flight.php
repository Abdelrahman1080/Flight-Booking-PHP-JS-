<?php
session_start();
header('Content-Type: application/json');

require '../configration/db.php';
require '../configration/helper-functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'passenger') {
    jsonResponse(false, 'Unauthorized');
}

$userId = $_SESSION['user_id'];
$flightId = isset($_POST['flight_id']) ? (int)$_POST['flight_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

if ($flightId <= 0) {
    jsonResponse(false, 'Flight ID is required');
}
if ($quantity <= 0) {
    jsonResponse(false, 'Quantity must be at least 1');
}

$passengerStmt = $conn->prepare('SELECT id, account_balance FROM passengers WHERE user_id = ? LIMIT 1');
$passengerStmt->bind_param('i', $userId);
$passengerStmt->execute();
$passenger = $passengerStmt->get_result()->fetch_assoc();
$passengerStmt->close();

if (!$passenger) {
    jsonResponse(false, 'Passenger profile not found');
}
$passengerId = (int)$passenger['id'];
$currentBalance = (float)$passenger['account_balance'];

$flightStmt = $conn->prepare('SELECT id, fees, is_completed FROM flights WHERE id = ? LIMIT 1');
$flightStmt->bind_param('i', $flightId);
$flightStmt->execute();
$flight = $flightStmt->get_result()->fetch_assoc();
$flightStmt->close();

if (!$flight) {
    jsonResponse(false, 'Flight not found');
}
if ((int)$flight['is_completed'] === 1) {
    jsonResponse(false, 'This flight is closed for booking');
}

$flightStmt2 = $conn->prepare('SELECT max_passengers FROM flights WHERE id = ? LIMIT 1');
$flightStmt2->bind_param('i', $flightId);
$flightStmt2->execute();
$flightDetails = $flightStmt2->get_result()->fetch_assoc();
$flightStmt2->close();

$seatStmt = $conn->prepare('SELECT COUNT(*) as booked FROM flight_bookings WHERE flight_id = ? AND status = "registered"');
$seatStmt->bind_param('i', $flightId);
$seatStmt->execute();
$seatData = $seatStmt->get_result()->fetch_assoc();
$seatStmt->close();
$bookedSeats = (int)($seatData['booked'] ?? 0);
$maxSeats = (int)($flightDetails['max_passengers'] ?? 0);
if ($bookedSeats + $quantity > $maxSeats) {
    jsonResponse(false, 'Not enough seats available');
}

$fee = (float)$flight['fees'];
$total = $fee * $quantity;

if ($currentBalance < $total) {
    jsonResponse(false, 'Insufficient balance');
}

$conn->begin_transaction();
try {
    $updateStmt = $conn->prepare('UPDATE passengers SET account_balance = account_balance - ? WHERE id = ? AND account_balance >= ?');
    $updateStmt->bind_param('dii', $total, $passengerId, $total);
    $updateStmt->execute();
    if ($updateStmt->affected_rows === 0) {
        $updateStmt->close();
        throw new Exception('Insufficient balance');
    }
    $updateStmt->close();

    $insertStmt = $conn->prepare('INSERT INTO flight_bookings (flight_id, passenger_id, status) VALUES (?, ?, "pending")');
    for ($i = 0; $i < $quantity; $i++) {
        $insertStmt->bind_param('ii', $flightId, $passengerId);
        $insertStmt->execute();
    }
    $insertStmt->close();

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    jsonResponse(false, 'Booking failed: ' . $e->getMessage());
}

$balanceStmt = $conn->prepare('SELECT account_balance FROM passengers WHERE id = ?');
$balanceStmt->bind_param('i', $passengerId);
$balanceStmt->execute();
$newBalance = (float)$balanceStmt->get_result()->fetch_column();
$balanceStmt->close();

jsonResponse(true, 'Booking confirmed', [
    'flight_id' => $flightId,
    'quantity' => $quantity,
    'charged' => $total,
    'new_balance' => $newBalance
]);
