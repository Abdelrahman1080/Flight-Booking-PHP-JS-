<?php
session_start();
header("Content-Type: application/json");

require "../configration/db.php";
require "../configration/helper-functions.php";

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'company') {
    jsonResponse(false, "Unauthorized");
}

$user_id = $_SESSION['user_id'];

 
$stmt = $conn->prepare("
    SELECT 
        c.id,
        u.name,
        c.bio,
        c.address,
        c.logo
    FROM companies c
    JOIN users u ON c.user_id = u.id
    WHERE u.id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$company = $stmt->get_result()->fetch_assoc();

if (!$company) {
    jsonResponse(false, "Company profile not found");
}

 
$stmt = $conn->prepare("
    SELECT id, flight_code, name, fees, is_completed
    FROM flights
    WHERE company_id = ?
");
$stmt->bind_param("i", $company['id']);
$stmt->execute();
$flights = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

jsonResponse(true, "Company profile loaded", [
    "company" => $company,
    "flights" => $flights
]);
