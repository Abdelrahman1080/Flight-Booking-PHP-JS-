<?php
session_start();
header('Content-Type: application/json');

require '../configration/db.php';
require '../configration/helper-functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'passenger') {
    jsonResponse(false, 'Unauthorized');
}

$userId = $_SESSION['user_id'];

$pStmt = $conn->prepare('SELECT id FROM passengers WHERE user_id = ? LIMIT 1');
$pStmt->bind_param('i', $userId);
$pStmt->execute();
$passenger = $pStmt->get_result()->fetch_assoc();
$pStmt->close();

if (!$passenger) {
    jsonResponse(false, 'Passenger profile not found');
}
$passengerId = (int)$passenger['id'];

$sql = 'SELECT fb.booked_at, fb.status, f.flight_code, f.name, f.fees
        FROM flight_bookings fb
        JOIN flights f ON fb.flight_id = f.id
        WHERE fb.passenger_id = ?
        ORDER BY fb.booked_at DESC, fb.id DESC
        LIMIT 100';

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $passengerId);
$stmt->execute();
$res = $stmt->get_result();

$transactions = [];
while ($row = $res->fetch_assoc()) {
    $transactions[] = [
        'date' => $row['booked_at'],
        'description' => 'Booking - ' . ($row['flight_code'] ?: $row['name'] ?: 'Flight'),
        'amount' => -1 * (float)$row['fees'],
        'status' => $row['status'],
        'type' => 'debit'
    ];
}
$stmt->close();

jsonResponse(true, 'Transactions loaded', [
    'transactions' => $transactions
]);
