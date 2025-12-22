<?php
header("Content-Type: application/json");
require "../configration/db.php";
require "../configration/helper-functions.php";

$data = $_POST;
$user_id = $data['user_id'] ?? '';
$account = $data['account_balance'] ?? 0;

if (!$user_id) {
    jsonResponse(false, "User ID required", null);
}

$uploadDir = __DIR__ . "/../uploads";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$photoPath = null;
$passportPath = null;

/* Photo */
if (!empty($_FILES['photo']['name'])) {
    $photoName = time() . "_" . $_FILES['photo']['name'];
    $photoPath = "uploads/" . $photoName;

    move_uploaded_file(
        $_FILES['photo']['tmp_name'],
        $uploadDir . "/" . $photoName
    );
}

/* Passport */
if (!empty($_FILES['passport_img']['name'])) {
    $passportName = time() . "_" . $_FILES['passport_img']['name'];
    $passportPath = "uploads/" . $passportName;

    move_uploaded_file(
        $_FILES['passport_img']['tmp_name'],
        $uploadDir . "/" . $passportName
    );
}

// Check if profile exists
$checkStmt = $conn->prepare("SELECT id FROM passenger_profiles WHERE user_id = ?");
$checkStmt->bind_param("i", $user_id);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    // Update existing profile
    $stmt = $conn->prepare("
        UPDATE passenger_profiles 
        SET account_balance = ?,
            photo = COALESCE(?, photo),
            passport_img = COALESCE(?, passport_img)
        WHERE user_id = ?
    ");
    $stmt->bind_param("dssi", $account, $photoPath, $passportPath, $user_id);
} else {
    // Insert new profile
    $stmt = $conn->prepare("
        INSERT INTO passenger_profiles (user_id, photo, passport_img, account_balance)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("issd", $user_id, $photoPath, $passportPath, $account);
}

if ($stmt->execute()) {
    jsonResponse(true, "Passenger profile completed", null);
}

jsonResponse(false, $stmt->error, null);
