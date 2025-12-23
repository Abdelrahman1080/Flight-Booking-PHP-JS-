<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../configration/db.php';
require_once __DIR__ . '/../configration/helper-functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'passenger') {
    jsonResponse(false, 'Unauthorized');
}

$userId = $_SESSION['user_id'];
$bookingId = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;

if ($bookingId <= 0) {
    jsonResponse(false, 'Invalid booking ID');
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

$bookingStmt = $conn->prepare('
    SELECT fb.id, fb.status, f.fees, f.is_completed 
    FROM flight_bookings fb
    JOIN flights f ON fb.flight_id = f.id
    WHERE fb.id = ? AND fb.passenger_id = ?
    LIMIT 1
');
$bookingStmt->bind_param('ii', $bookingId, $passengerId);
$bookingStmt->execute();
$booking = $bookingStmt->get_result()->fetch_assoc();
$bookingStmt->close();

if (!$booking) {
    jsonResponse(false, 'Booking not found or does not belong to you');
}

if ($booking['status'] === 'cancelled') {
    jsonResponse(false, 'Booking is already cancelled');
}

if ($booking['status'] === 'completed' || (int)$booking['is_completed'] === 1) {
    jsonResponse(false, 'Cannot cancel completed flights');
}

$refundAmount = (float)$booking['fees'];

$conn->begin_transaction();
try {

    $updateStmt = $conn->prepare('UPDATE flight_bookings SET status = "cancelled" WHERE id = ?');
    $updateStmt->bind_param('i', $bookingId);
    $updateStmt->execute();
    $updateStmt->close();
    
    $refundStmt = $conn->prepare('UPDATE passengers SET account_balance = account_balance + ? WHERE id = ?');
    $refundStmt->bind_param('di', $refundAmount, $passengerId);
    $refundStmt->execute();
    $refundStmt->close();
    
    $conn->commit();
    
    $newBalance = $currentBalance + $refundAmount;
    jsonResponse(true, 'Booking cancelled and refunded', [
        'refund_amount' => $refundAmount,
        'new_balance' => $newBalance
    ]);
} catch (Exception $e) {
    $conn->rollback();
    jsonResponse(false, 'Cancellation failed: ' . $e->getMessage());
}
