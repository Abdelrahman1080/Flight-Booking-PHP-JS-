<?php
session_start();
header("Content-Type: application/json");
require "../configration/db.php";
require "../configration/helper-functions.php";

$data = json_decode(file_get_contents("php://input"), true);

$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

$stmt = $conn->prepare("SELECT id, password, user_type FROM users WHERE email = ?");
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

$_SESSION['user_id'] = $user['id'];
$_SESSION['user_type'] = $user['user_type'];

jsonResponse(true, "Login successful", [
    "user_type" => $user['user_type']
]);
