<?php
session_start();
header("Content-Type: application/json");

require "../configration/db.php";
require "../configration/helper-functions.php";

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'company') {
    jsonResponse(false, "Unauthorized");
}

$user_id = $_SESSION['user_id'];

$company_name = $_POST['name'] ?? '';
$bio = $_POST['bio'] ?? '';
$address = $_POST['address'] ?? '';
$account_balance = $_POST['account_balance'] ?? null;

if (!$company_name) {
    jsonResponse(false, "Company name is required");
}

 
$logoPath = null;
if (!empty($_FILES['logo']['name'])) {
    $logoPath = "uploads/" . time() . "_" . $_FILES['logo']['name'];
    move_uploaded_file($_FILES['logo']['tmp_name'], "../" . $logoPath);
}

 
if ($logoPath && $account_balance !== null) {
    $stmt = $conn->prepare("
        UPDATE companies
        SET comapny_name = ?, bio = ?, address = ?, logo = ?, account_balance = ?
        WHERE user_id = ?
    ");
    $stmt->bind_param("sssdi", $company_name, $bio, $address, $logoPath, $account_balance, $user_id);
} elseif ($logoPath) {
    $stmt = $conn->prepare("
        UPDATE companies
        SET comapny_name = ?, bio = ?, address = ?, logo = ?
        WHERE user_id = ?
    ");
    $stmt->bind_param("ssssi", $company_name, $bio, $address, $logoPath, $user_id);
} elseif ($account_balance !== null) {
    $stmt = $conn->prepare("
        UPDATE companies
        SET comapny_name = ?, bio = ?, address = ?, account_balance = ?
        WHERE user_id = ?
    ");
    $stmt->bind_param("sssdi", $company_name, $bio, $address, $account_balance, $user_id);
} else {
    $stmt = $conn->prepare("
        UPDATE companies
        SET comapny_name = ?, bio = ?, address = ?
        WHERE user_id = ?
    ");
    $stmt->bind_param("sssi", $company_name, $bio, $address, $user_id);
}

if ($stmt->execute()) {
    jsonResponse(true, "Profile updated successfully");
}

jsonResponse(false, "Update failed");
