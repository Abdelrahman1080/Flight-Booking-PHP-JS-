<?php
session_start();
header("Content-Type: application/json");

require "../configration/db.php";
require "../configration/helper-functions.php";

if (!isset($_SESSION['user_id'])) {
    jsonResponse(false, "Unauthorized");
}

$user_id   = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

$chat_with = $_GET['chat_with'] ?? null;
$flight_id = $_GET['flight_id'] ?? null;

$sql = "
    SELECT sender_type, sender_id, receiver_id, message, created_at
    FROM messages
    WHERE ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))
";

$params = [$user_id, $chat_with, $chat_with, $user_id];
$types  = "iiii";

if ($flight_id) {
    $sql .= " AND flight_id = ?";
    $params[] = $flight_id;
    $types .= "i";
}

$sql .= " ORDER BY created_at ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();

$messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

foreach ($messages as &$msg) {
    $msg['current_user_id'] = $user_id;
}

jsonResponse(true, "Messages loaded", $messages);
