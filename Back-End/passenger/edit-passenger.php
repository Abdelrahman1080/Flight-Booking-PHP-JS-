<?php
session_start();
header("Content-Type: application/json");

require "../configration/db.php";
require "../configration/helper-functions.php";

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'passenger') {
    jsonResponse(false, "Unauthorized");
}

$user_id = $_SESSION['user_id'];

$full_name = $_POST['full_name'] ?? '';
$tel = $_POST['tel'] ?? '';

if (!$full_name) {
    jsonResponse(false, "Full name is required");
}

// Handle photo upload
$photoPath = null;
if (!empty($_FILES['photo']['name'])) {
    $photoPath = "uploads/" . time() . "_" . $_FILES['photo']['name'];
    move_uploaded_file($_FILES['photo']['tmp_name'], "../" . $photoPath);
}

// Handle passport upload
$passportPath = null;
if (!empty($_FILES['passport']['name'])) {
    $passportPath = "uploads/" . time() . "_passport_" . $_FILES['passport']['name'];
    move_uploaded_file($_FILES['passport']['tmp_name'], "../" . $passportPath);
}

// Update users table with name and phone
$stmt = $conn->prepare("
    UPDATE users SET name = ?, tel = ? WHERE id = ?
");
$stmt->bind_param("ssi", $full_name, $tel, $user_id);
$stmt->execute();

// Update passengers table
if ($photoPath && $passportPath) {
    $stmt = $conn->prepare("
        UPDATE passengers
        SET full_name = ?, photo = ?, passport_img = ?
        WHERE user_id = ?
    ");
    $stmt->bind_param("sssi", $full_name, $photoPath, $passportPath, $user_id);
} elseif ($photoPath) {
    $stmt = $conn->prepare("
        UPDATE passengers
        SET full_name = ?, photo = ?
        WHERE user_id = ?
    ");
    $stmt->bind_param("ssi", $full_name, $photoPath, $user_id);
} elseif ($passportPath) {
    $stmt = $conn->prepare("
        UPDATE passengers
        SET full_name = ?, passport_img = ?
        WHERE user_id = ?
    ");
    $stmt->bind_param("ssi", $full_name, $passportPath, $user_id);
} else {
    $stmt = $conn->prepare("
        UPDATE passengers
        SET full_name = ?
        WHERE user_id = ?
    ");
    $stmt->bind_param("si", $full_name, $user_id);
}

if ($stmt->execute()) {
    jsonResponse(true, "Profile updated successfully");
}

jsonResponse(false, "Update failed");
?>
