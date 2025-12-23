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

$flight_id = $_GET['flight_id'] ?? null;

$sql = "
    SELECT sender_type, sender_id, message, created_at
    FROM messages
    WHERE (sender_id = ? OR receiver_id = ?)
";

$params = [$user_id, $user_id];
$types  = "ii";

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

jsonResponse(true, "Messages loaded", $messages);
