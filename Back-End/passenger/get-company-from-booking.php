<?php
session_start();
header('Content-Type: application/json');

require '../configration/db.php';
require '../configration/helper-functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'passenger') {
    jsonResponse(false, 'Unauthorized');
}

$bookingId = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;

if (!$bookingId) {
    jsonResponse(false, 'Booking ID is required');
}

$stmt = $conn->prepare('
    SELECT u.id AS company_user_id, u.name AS company_name, u.email AS company_email
    FROM flight_bookings fb
    JOIN flights f ON fb.flight_id = f.id
    JOIN companies c ON f.company_id = c.id
    JOIN users u ON c.user_id = u.id
    WHERE fb.id = ?
    LIMIT 1
');
$stmt->bind_param('i', $bookingId);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$result) {
    jsonResponse(false, 'Company not found for this booking');
}

jsonResponse(true, 'Company information loaded', $result);
