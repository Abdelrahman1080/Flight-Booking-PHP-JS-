<?php


header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

function jsonResponse($status, $message, $data = null) {
	echo json_encode([
		'success' => (bool)$status,
		'message' => $message,
		'data'    => $data
	]);
	exit;
}

ob_start();
require_once __DIR__ . '/../configration/db.php';
ob_end_clean();

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
	jsonResponse(false, 'Not authenticated', null);
}

$companyRow = null;
$companyId = null;
$companyName = null;
$companyLogo = null;
$companyBio = null;
$companyAddress = null;
$companyLocation = null;
$companyAccount = 0;

if (isset($conn)) {
	$sql = "SELECT id, comapny_name AS company_name, bio, address, location, logo, account_balance FROM companies WHERE user_id = ? LIMIT 1";
	if ($stmt = mysqli_prepare($conn, $sql)) {
		mysqli_stmt_bind_param($stmt, 'i', $userId);
		mysqli_stmt_execute($stmt);
		$res = mysqli_stmt_get_result($stmt);
		$companyRow = mysqli_fetch_assoc($res) ?: null;
		error_log("DEBUG: user_id=$userId, company row: " . json_encode($companyRow));
		mysqli_stmt_close($stmt);
	}
}

if ($companyRow) {
	$companyId     = $companyRow['id'];
	$companyName   = $companyRow['company_name'] ?: ($_SESSION['user_name'] ?? 'Company');
	$companyLogo   = $companyRow['logo'] ?: null;
	$companyBio    = $companyRow['bio'] ?: '';
	$companyAddress= $companyRow['address'] ?: '';
	$companyLocation= $companyRow['location'] ?: '';
	$companyAccount= (float)($companyRow['account_balance'] ?? 0);
} else {
	$companyName = $_SESSION['user_name'] ?? 'Company';
}

$initials = 'CO';
if ($companyName) {
	$matches = [];
	preg_match_all('/\b\w/u', $companyName, $matches);
	$initials = strtoupper(substr(implode('', $matches[0]), 0, 2));
}

$flights = [];
if ($companyId && isset($conn)) {
	$sql = 'SELECT id, flight_code, name, fees, is_completed FROM flights WHERE company_id = ?';
	if ($stmt = mysqli_prepare($conn, $sql)) {
		mysqli_stmt_bind_param($stmt, 'i', $companyId);
		mysqli_stmt_execute($stmt);
		$res = mysqli_stmt_get_result($stmt);
		while ($row = mysqli_fetch_assoc($res)) {
			$flights[] = $row;
		}
		mysqli_stmt_close($stmt);
	}
}

$stats = [
	'activeFlights'        => count($flights),
	'totalBookings'        => 0,
	'pendingConfirmations' => 0,
	'revenueThisMonth'     => '$0'
];

$recentBookings = [];
if ($companyId && isset($conn)) {
	$sql = "SELECT 
		fb.id, 
		fb.status, 
		fb.booked_at, 
		f.flight_code, 
		f.name as flight_name,
		p.full_name as passenger_name
	FROM flight_bookings fb
	JOIN flights f ON fb.flight_id = f.id
	JOIN passengers p ON fb.passenger_id = p.id
	WHERE f.company_id = ?
	ORDER BY fb.booked_at DESC
	LIMIT 5";
	
	if ($stmt = mysqli_prepare($conn, $sql)) {
		mysqli_stmt_bind_param($stmt, 'i', $companyId);
		mysqli_stmt_execute($stmt);
		$res = mysqli_stmt_get_result($stmt);
		while ($row = mysqli_fetch_assoc($res)) {
			$recentBookings[] = [
				'id' => $row['id'],
				'passenger' => $row['passenger_name'] ?: 'Unknown',
				'flight' => $row['flight_code'] . ' - ' . $row['flight_name'],
				'status' => ucfirst($row['status']),
				'date' => $row['booked_at']
			];
		}
		mysqli_stmt_close($stmt);
	}
}

$totalBookings = 0;
$pendingBookings = 0;
if ($companyId && isset($conn)) {
	$sql = "SELECT COUNT(*) as total FROM flight_bookings fb
			JOIN flights f ON fb.flight_id = f.id
			WHERE f.company_id = ?";
	if ($stmt = mysqli_prepare($conn, $sql)) {
		mysqli_stmt_bind_param($stmt, 'i', $companyId);
		mysqli_stmt_execute($stmt);
		$res = mysqli_stmt_get_result($stmt);
		if ($row = mysqli_fetch_assoc($res)) {
			$totalBookings = (int)$row['total'];
		}
		mysqli_stmt_close($stmt);
	}
	
	$sql = "SELECT COUNT(*) as pending FROM flight_bookings fb
			JOIN flights f ON fb.flight_id = f.id
			WHERE f.company_id = ? AND fb.status = 'pending'";
	if ($stmt = mysqli_prepare($conn, $sql)) {
		mysqli_stmt_bind_param($stmt, 'i', $companyId);
		mysqli_stmt_execute($stmt);
		$res = mysqli_stmt_get_result($stmt);
		if ($row = mysqli_fetch_assoc($res)) {
			$pendingBookings = (int)$row['pending'];
		}
		mysqli_stmt_close($stmt);
	}
	
	$stats['totalBookings'] = $totalBookings;
	$stats['pendingConfirmations'] = $pendingBookings;
}

$payload = [
	'company' => [
		'id'    => $companyId,
		'name'  => $companyName,
		'company_name' => $companyName,
		'avatarInitials' => $initials,
		'logo'  => $companyLogo
	],
	'profile' => [
		'name'     => $companyName,
		'company_name' => $companyName,
		'bio'      => $companyBio,
		'address'  => $companyAddress,
		'location' => $companyLocation,
		'email'    => $_SESSION['user_email'] ?? null,
		'logo'     => $companyLogo,
		'account'  => $companyAccount,
		'flights'  => $flights
	],
	'stats' => $stats,
	'recentBookings' => $recentBookings
];

jsonResponse(true, 'Company dashboard loaded', $payload);

?>
