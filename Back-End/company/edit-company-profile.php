<?php
session_start();
header("Content-Type: application/json");

require "../configration/db.php";
require "../configration/helper-functions.php";

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'company') {
    jsonResponse(false, "Unauthorized");
}

$user_id = $_SESSION['user_id'];

$name = $_POST['name'] ?? '';
$bio = $_POST['bio'] ?? '';
$address = $_POST['address'] ?? '';

if (!$name) {
    jsonResponse(false, "Name is required");
}

 
$logoPath = null;
if (!empty($_FILES['logo']['name'])) {
    $logoPath = "uploads/" . time() . "_" . $_FILES['logo']['name'];
    move_uploaded_file($_FILES['logo']['tmp_name'], "../" . $logoPath);
}

 
$stmt = $conn->prepare("
    UPDATE users SET name = ? WHERE id = ?
");
$stmt->bind_param("si", $name, $user_id);
$stmt->execute();

 
if ($logoPath) {
    $stmt = $conn->prepare("
        UPDATE companies
        SET bio = ?, address = ?, logo = ?
        WHERE user_id = ?
    ");
    $stmt->bind_param("sssi", $bio, $address, $logoPath, $user_id);
} else {
    $stmt = $conn->prepare("
        UPDATE companies
        SET bio = ?, address = ?
        WHERE user_id = ?
    ");
    $stmt->bind_param("ssi", $bio, $address, $user_id);
}

if ($stmt->execute()) {
    jsonResponse(true, "Profile updated successfully");
}

jsonResponse(false, "Update failed");
