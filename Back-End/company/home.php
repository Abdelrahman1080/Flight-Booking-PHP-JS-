<?php
session_start();
header("Content-Type: application/json");

require "../configration/db.php";
require "../configration/helper-functions.php";

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'company') {
    jsonResponse(false, "Unauthorized");
}

$userId = $_SESSION['user_id'];

/* Company Info */
$stmt = $conn->prepare("
    SELECT c.id AS company_id, u.name, c.logo
    FROM companies c
    JOIN users u ON c.user_id = u.id
    WHERE u.id = ?
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$company = $stmt->get_result()->fetch_assoc();

if (!$company) {
    jsonResponse(false, "Company not found");
}

/* Flights List */
$stmt = $conn->prepare("
    SELECT id, flight_code, name, fees, is_completed
    FROM flights
    WHERE company_id = ?
    ORDER BY created_at DESC
");
$stmt->bind_param("i", $company['company_id']);
$stmt->execute();
$flights = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

jsonResponse(true, "Company home data", [
    "company" => [
        "name" => $company['name'],
        "logo" => $company['logo']
    ],
    "flights_count" => count($flights),
    "flights" => $flights
]);
