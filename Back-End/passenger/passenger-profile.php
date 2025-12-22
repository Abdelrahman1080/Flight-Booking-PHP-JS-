<?php
session_start();
header("Content-Type: application/json");

require "../configration/db.php";
require "../configration/helper-functions.php";

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'passenger') {
    jsonResponse(false, "Unauthorized", null);
}

$user_id = $_SESSION['user_id'];

// Fetch user data
$stmt = $conn->prepare("SELECT id, name, email, tel, user_type, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    jsonResponse(false, "User not found", null);
}

// Fetch passenger profile data
$stmt = $conn->prepare("SELECT * FROM passenger_profiles WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$profile = $result->fetch_assoc();

// Build response with user and profile data
$data = [
    "user" => [
        "id" => $user['id'],
        "name" => $user['name'],
        "email" => $user['email'],
        "tel" => $user['tel'],
        "user_type" => $user['user_type']
    ],
    "profile" => $profile
];

jsonResponse(true, "Passenger profile loaded", $data);
