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

$userId   = $_SESSION['user_id'] ?? null;
$userName = $_SESSION['user_name'] ?? null;

if (!$userId) {
	jsonResponse(false, 'Not authenticated', null);
}

if (!$userName) {
	if (isset($conn)) {
		$sql = 'SELECT u.name, p.full_name FROM users u LEFT JOIN passengers p ON u.id = p.user_id WHERE u.id = ? LIMIT 1';
		if ($stmt = mysqli_prepare($conn, $sql)) {
			mysqli_stmt_bind_param($stmt, 'i', $userId);
			mysqli_stmt_execute($stmt);
			$res = mysqli_stmt_get_result($stmt);
			if ($row = mysqli_fetch_assoc($res)) {
				$userName = $row['full_name'] ?: ($row['name'] ?? 'User');
			}
			mysqli_stmt_close($stmt);
		}
	}
}

$initials = 'U';
if ($userName) {
	$matches = [];
	preg_match_all('/\b\w/u', $userName, $matches);
	$initials = strtoupper(substr(implode('', $matches[0]), 0, 2));
}


$passengerId = null;
$accountBalance = 0;
$photo = null;
$passportImg = null;
$fullName = null;
if (isset($conn)) {
	$sql = 'SELECT id, full_name, account_balance, photo, passport_img FROM passengers WHERE user_id = ? LIMIT 1';
	if ($stmt = mysqli_prepare($conn, $sql)) {
		mysqli_stmt_bind_param($stmt, 'i', $userId);
		mysqli_stmt_execute($stmt);
		$res = mysqli_stmt_get_result($stmt);
		if ($row = mysqli_fetch_assoc($res)) {
			$passengerId   = $row['id'];
			$fullName      = $row['full_name'] ?? null;
			$accountBalance= (float)($row['account_balance'] ?? 0);
			$photo         = $row['photo'] ?? null;
			$passportImg   = $row['passport_img'] ?? null;
			if ($fullName) {
				$userName = $fullName;
				$matches = [];
				preg_match_all('/\b\w/u', $userName, $matches);
				$initials = strtoupper(substr(implode('', $matches[0]), 0, 2));
			}
		}
		mysqli_stmt_close($stmt);
	}
}

$upcomingFlights = [];
if (isset($conn) && $passengerId) {
	$sql = "
		SELECT 
			fb.id as booking_id,
			f.id as flight_id,
			f.flight_code,
			f.name as flight_name,
			f.fees,
			f.start_datetime,
			f.end_datetime,
			f.is_completed,
			c.comapny_name,
			fb.status,
			GROUP_CONCAT(fi.city_name ORDER BY fi.city_order SEPARATOR ' → ') as route
		FROM flight_bookings fb
		JOIN flights f ON fb.flight_id = f.id
		JOIN companies c ON f.company_id = c.id
		LEFT JOIN flight_itinerary fi ON f.id = fi.flight_id
		WHERE fb.passenger_id = ? AND (fb.status = 'registered' OR fb.status = 'pending') AND f.is_completed = 0
		GROUP BY f.id
		ORDER BY f.start_datetime ASC
		LIMIT 10
	";
	if ($stmt = mysqli_prepare($conn, $sql)) {
		mysqli_stmt_bind_param($stmt, 'i', $passengerId);
		mysqli_stmt_execute($stmt);
		$res = mysqli_stmt_get_result($stmt);
		while ($row = mysqli_fetch_assoc($res)) {
			$startDt = new DateTime($row['start_datetime']);
			$endDt = new DateTime($row['end_datetime']);
			$upcomingFlights[] = [
				'flightNumber' => $row['flight_code'] ?? 'N/A',
				'airline' => $row['comapny_name'] ?? 'Unknown',
				'date' => $startDt->format('M d, Y'),
				'time' => $startDt->format('g:i A') . ' - ' . $endDt->format('g:i A'),
				'route' => $row['route'] ?? 'N/A',
				'cities' => $row['route'] ?? 'N/A',
				'status' => ucfirst($row['status']),
				'bookingId' => $row['booking_id'],
				'flightId' => $row['flight_id'],
				'fees' => $row['fees']
			];
		}
		mysqli_stmt_close($stmt);
	}
}

$stats = [
	'upcomingFlights' => count($upcomingFlights),
	'completedTrips'  => 0,
	'pendingBookings' => 0,
	'skyMiles'        => 0
];

$payload = [
	'user' => [
		'id'        => $userId,
		'name'      => $userName ?: 'User',
		'avatarInitials' => $initials
	],
	'passenger' => [
		'id'     => $passengerId,
		'account'=> $accountBalance,
		'photo'  => $photo,
		'passportImg' => $passportImg
	],
	'stats' => $stats,
	'upcomingFlights' => $upcomingFlights
];

jsonResponse(true, 'Dashboard data loaded', $payload);

?>
