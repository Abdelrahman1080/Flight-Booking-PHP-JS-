<?php
session_start();
header("Content-Type: application/json");

require "../configration/db.php";
require "../configration/helper-functions.php";

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'company') {
    jsonResponse(false, "Unauthorized");
}

$stmt = $conn->prepare("SELECT id FROM companies WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$company = $stmt->get_result()->fetch_assoc();

$stmt = $conn->prepare("
    SELECT message, sender_type, created_at
    FROM messages
    WHERE receiver_id = ?
    ORDER BY created_at DESC
");
$stmt->bind_param("i", $company['id']);
$stmt->execute();
$messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

jsonResponse(true, "Messages loaded", $messages);
