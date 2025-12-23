<?php
session_start();
header('Content-Type: application/json');

require '../configration/db.php';
require '../configration/helper-functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'company') {
    jsonResponse(false, 'Unauthorized');
}

$userId = $_SESSION['user_id'];

$cStmt = $conn->prepare('SELECT id FROM companies WHERE user_id = ? LIMIT 1');
$cStmt->bind_param('i', $userId);
$cStmt->execute();
$company = $cStmt->get_result()->fetch_assoc();
$cStmt->close();

if (!$company) {
    jsonResponse(false, 'Company not found');
}
$companyId = (int)$company['id'];


$sql = 'SELECT fb.booked_at, fb.id as booking_id, p.user_id as passenger_id, u.name as passenger_name,
               f.flight_code, f.name as flight_name, f.fees,
               COUNT(*) as ticket_count
        FROM flight_bookings fb
        JOIN flights f ON fb.flight_id = f.id
        JOIN passengers p ON fb.passenger_id = p.id
        JOIN users u ON p.user_id = u.id
        WHERE f.company_id = ? AND fb.status = "registered"
        GROUP BY DATE(fb.booked_at), fb.passenger_id, f.id
        ORDER BY fb.booked_at DESC
        LIMIT 100';

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $companyId);
$stmt->execute();
$res = $stmt->get_result();

$transactions = [];
$totalRevenue = 0;
while ($row = $res->fetch_assoc()) {
    $ticketCount = (int)$row['ticket_count'];
    $fee = (float)$row['fees'];
    $revenue = $ticketCount * $fee;
    $totalRevenue += $revenue;
    
    $transactions[] = [
        'date' => $row['booked_at'],
        'description' => 'Booking - ' . ($row['flight_code'] ?: $row['flight_name'] ?: 'Flight') . ' (' . $ticketCount . ' ticket' . ($ticketCount > 1 ? 's' : '') . ')',
        'passenger' => $row['passenger_name'],
        'amount' => $revenue,
        'tickets' => $ticketCount,
        'fee' => $fee,
        'type' => 'credit'
    ];
}
$stmt->close();

jsonResponse(true, 'Company revenue loaded', [
    'transactions' => $transactions,
    'total_revenue' => $totalRevenue
]);
