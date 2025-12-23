<?php
session_start();
header("Content-Type: application/json");

require "../configration/db.php";
require "../configration/helper-functions.php";

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'passenger') {
    jsonResponse(false, "Unauthorized");
}

$user_id = $_SESSION['user_id'];

$name = $_POST['name'] ?? '';
$tel  = $_POST['tel'] ?? '';

if (!$name) {
    jsonResponse(false, "Name is required");
}


$photoPath = null;
$passportPath = null;

if (!empty($_FILES['photo']['name'])) {
    $photoPath = "uploads/" . time() . "_" . $_FILES['photo']['name'];
    move_uploaded_file($_FILES['photo']['tmp_name'], "../" . $photoPath);
}

if (!empty($_FILES['passport_img']['name'])) {
    $passportPath = "uploads/" . time() . "_" . $_FILES['passport_img']['name'];
    move_uploaded_file($_FILES['passport_img']['tmp_name'], "../" . $passportPath);
}

$stmt = $conn->prepare("
    UPDATE users SET name = ?, tel = ? WHERE id = ?
");
$stmt->bind_param("ssi", $name, $tel, $user_id);
$stmt->execute();


if ($photoPath || $passportPath) {

    $stmt = $conn->prepare("
        UPDATE passengers
        SET 
            photo = COALESCE(?, photo),
            passport_img = COALESCE(?, passport_img)
        WHERE user_id = ?
    ");
    $stmt->bind_param("ssi", $photoPath, $passportPath, $user_id);

} else {
    $stmt = $conn->prepare("
        UPDATE passengers WHERE user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
}

if ($stmt->execute()) {
    jsonResponse(true, "Passenger profile updated");
}

jsonResponse(false, "Update failed");
