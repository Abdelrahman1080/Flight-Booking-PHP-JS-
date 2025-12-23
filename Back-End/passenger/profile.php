<?php
session_start();
header("Content-Type: application/json");

require "../configration/db.php";
require "../configration/helper-functions.php";

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'passenger') {
    jsonResponse(false, "Unauthorized");
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT 
        u.name,
        u.email,
        u.tel,
        p.photo,
        p.passport_img,
        p.account_balance
    FROM passengers p
    JOIN users u ON p.user_id = u.id
    WHERE u.id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();

if (!$profile) {
    jsonResponse(false, "Passenger profile not found");
}

jsonResponse(true, "Passenger profile loaded", $profile);
