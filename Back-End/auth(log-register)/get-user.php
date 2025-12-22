<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../configration/db.php';

$response = function($ok, $msg = '', $data = null) {
    echo json_encode([
        'success' => $ok,
        'message' => $msg,
        'data' => $data
    ]);
    exit;
};

if (!isset($_SESSION['user_id'])) {
    $response(false, 'No active session');
}

$user_id = intval($_SESSION['user_id']);

$stmt = $conn->prepare('SELECT id as user_id, name, email, tel, user_type FROM users WHERE id = ? LIMIT 1');
if (!$stmt) {
    $response(false, 'DB error preparing');
}
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result ? $result->fetch_assoc() : null;

if (!$user) {
    $response(false, 'User not found');
}

// Enhance response with company name or passenger ID based on user type
// This establishes the user's "route/folder" in the session
if ($user['user_type'] === 'company') {
    $stmt = $conn->prepare("SELECT comapny_name AS company_name, id AS company_id FROM companies WHERE user_id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $company = $result ? $result->fetch_assoc() : null;
        if ($company) {
            $user['company_name'] = $company['company_name']; // User's "route/folder" - e.g., "Kamashka"
            $user['company_id'] = $company['company_id'];
        }
        $stmt->close();
    }
} elseif ($user['user_type'] === 'passenger') {
    $stmt = $conn->prepare("SELECT id AS passenger_id FROM passengers WHERE user_id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $passenger = $result ? $result->fetch_assoc() : null;
        if ($passenger) {
            $user['passenger_id'] = $passenger['passenger_id']; // User's "route/folder"
        }
        $stmt->close();
    }
}

$response(true, 'Active session', $user);
