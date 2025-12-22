<?php
session_start();
header("Content-Type: application/json");
require "../configration/db.php";
require "../configration/helper-functions.php";

$data = json_decode(file_get_contents("php://input"), true);

$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

$stmt = $conn->prepare("SELECT id, name, email, password, user_type FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    jsonResponse(false, "Invalid email or password");
}

$user = $result->fetch_assoc();

if (!password_verify($password, $user['password'])) {
    jsonResponse(false, "Invalid email or password");
}

// Regenerate session ID for security and persistence
session_regenerate_id(true);

// Store user data in session
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_type'] = $user['user_type'];
$_SESSION['user_name'] = $user['name'] ?? null;
$_SESSION['user_email'] = $user['email'] ?? null;
$_SESSION['login_time'] = time();

// For company users, store company context (the "route/folder")
if ($user['user_type'] === 'company') {
    $stmt = $conn->prepare("SELECT comapny_name AS company_name, id AS company_id FROM companies WHERE user_id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $user['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $company = $result->fetch_assoc();
        if ($company) {
            $_SESSION['company_name'] = $company['company_name'];
            $_SESSION['company_id'] = $company['company_id'];
        }
        $stmt->close();
    }
}

jsonResponse(true, "Login successful", [
    "user_type" => $user['user_type'],
    "user_id"   => $user['id'],
    "company_name" => $_SESSION['company_name'] ?? null
]);
