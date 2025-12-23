<?php
session_start();
header("Content-Type: application/json");

require "../configration/db.php";
require "../configration/helper-functions.php";

if (!isset($_SESSION['user_id'])) {
    jsonResponse(false, "Unauthorized");
}

$sender_id   = $_SESSION['user_id'];
$sender_type = $_SESSION['user_type'];

$receiver_id = $_POST['receiver_id'] ?? '';
$flight_id   = $_POST['flight_id'] ?? null;
$message     = $_POST['message'] ?? '';

if (!$receiver_id || !$message) {
    jsonResponse(false, "Receiver and message are required");
}

$stmt = $conn->prepare("
    INSERT INTO messages (flight_id, sender_type, sender_id, receiver_id, message)
    VALUES (?, ?, ?, ?, ?)
");
$stmt->bind_param(
    "isiis",
    $flight_id,
    $sender_type,
    $sender_id,
    $receiver_id,
    $message
);

if ($stmt->execute()) {
    jsonResponse(true, "Message sent");
}

jsonResponse(false, "Failed to send message");
