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
		$sql = 'SELECT name FROM users WHERE id = ? LIMIT 1';
		if ($stmt = mysqli_prepare($conn, $sql)) {
			mysqli_stmt_bind_param($stmt, 'i', $userId);
			mysqli_stmt_execute($stmt);
			$res = mysqli_stmt_get_result($stmt);
			if ($row = mysqli_fetch_assoc($res)) {
				$userName = $row['name'] ?? 'User';
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


// Load passenger profile (ensures uniqueness per account)
$passengerId = null;
$accountBalance = 0;
$photo = null;
$passportImg = null;
if (isset($conn)) {
	$sql = 'SELECT id, account_balance, photo, passport_img FROM passengers WHERE user_id = ? LIMIT 1';
	if ($stmt = mysqli_prepare($conn, $sql)) {
		mysqli_stmt_bind_param($stmt, 'i', $userId);
		mysqli_stmt_execute($stmt);
		$res = mysqli_stmt_get_result($stmt);
		if ($row = mysqli_fetch_assoc($res)) {
			$passengerId   = $row['id'];
			$accountBalance= (float)($row['account_balance'] ?? 0);
			$photo         = $row['photo'] ?? null;
			$passportImg   = $row['passport_img'] ?? null;
		}
		mysqli_stmt_close($stmt);
	}
}

$stats = [
	'upcomingFlights' => 0,
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
	'stats' => $stats
];

jsonResponse(true, 'Dashboard data loaded', $payload);

?>
