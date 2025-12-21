<?php
header("Content-Type: application/json");
require "../configration/db.php";
require "../configration/helper-functions.php";

$data = json_decode(file_get_contents("php://input"), true);

$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';
$tel = $data['tel'] ?? '';
$user_type = $data['user_type'] ?? '';

if (!$name || !$email || !$password || !$user_type) {
    jsonResponse(false, "Missing required fields");
}

if (!in_array($user_type, ['company', 'passenger'])) {
    jsonResponse(false, "Invalid user type");
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// check email exists
$check = $conn->prepare("SELECT id FROM users WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    jsonResponse(false, "Email already exists");
}

$stmt = $conn->prepare("
    INSERT INTO users (name, email, password, tel, user_type)
    VALUES (?, ?, ?, ?, ?)
");

$stmt->bind_param("sssss", $name, $email, $hashedPassword, $tel, $user_type);

if ($stmt->execute()) {
    jsonResponse(true, "Registered successfully", [
        "user_id" => $stmt->insert_id,
        "user_type" => $user_type
    ]);
}

jsonResponse(false, "Registration failed");
