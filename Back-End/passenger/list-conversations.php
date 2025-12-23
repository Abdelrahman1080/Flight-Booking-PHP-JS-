<?php
session_start();
header("Content-Type: application/json");

require "../configration/db.php";
require "../configration/helper-functions.php";

if (!isset($_SESSION['user_id'])) {
    jsonResponse(false, "Unauthorized");
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT 
        CASE 
            WHEN m.sender_id = ? THEN m.receiver_id 
            ELSE m.sender_id 
        END as other_user_id,
        m.flight_id,
        u.name as other_user_name,
        u.user_type as other_user_type,
        f.flight_code,
        MAX(m.created_at) as last_message_time,
        (SELECT message FROM messages m2 
         WHERE ((m2.sender_id = ? AND m2.receiver_id = other_user_id) 
             OR (m2.sender_id = other_user_id AND m2.receiver_id = ?))
         AND (m.flight_id IS NULL OR m2.flight_id = m.flight_id)
         ORDER BY m2.created_at DESC LIMIT 1) as last_message
    FROM messages m
    LEFT JOIN users u ON u.id = CASE 
        WHEN m.sender_id = ? THEN m.receiver_id 
        ELSE m.sender_id 
    END
    LEFT JOIN flights f ON m.flight_id = f.id
    WHERE m.sender_id = ? OR m.receiver_id = ?
    GROUP BY other_user_id, m.flight_id
    ORDER BY last_message_time DESC
");

$stmt->bind_param("iiiiii", $user_id, $user_id, $user_id, $user_id, $user_id, $user_id);
$stmt->execute();
$conversations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

jsonResponse(true, "Conversations loaded", $conversations);
