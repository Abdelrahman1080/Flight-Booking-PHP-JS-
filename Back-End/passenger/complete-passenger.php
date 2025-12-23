<?php
header("Content-Type: application/json");
require "../configration/db.php";
require "../configration/helper-functions.php";

$data = $_POST;
$action = $data['action'] ?? '';

if ($action === 'update_balance') {
    session_start();
    $user_id = $_SESSION['user_id'] ?? null;
    
    if (!$user_id) {
        jsonResponse(false, "User not authenticated", null);
        exit;
    }
    
    $newBalance = floatval($data['account_balance'] ?? 0);
    
    if ($newBalance < 0) {
        jsonResponse(false, "Balance cannot be negative", null);
        exit;
    }
    
    $stmt = $conn->prepare("UPDATE passengers SET account_balance = ? WHERE user_id = ?");
    $stmt->bind_param("di", $newBalance, $user_id);
    
    if ($stmt->execute()) {
        jsonResponse(true, "Balance updated successfully", null);
    } else {
        jsonResponse(false, $stmt->error, null);
    }
    exit;
}

$user_id = $data['user_id'] ?? '';
$full_name = $data['full_name'] ?? '';
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

if (!empty($_FILES['photo']['name'])) {
    $photoName = time() . "_" . $_FILES['photo']['name'];
    $photoPath = "uploads/" . $photoName;

    move_uploaded_file(
        $_FILES['photo']['tmp_name'],
        $uploadDir . "/" . $photoName
    );
}

if (!empty($_FILES['passport_img']['name'])) {
    $passportName = time() . "_" . $_FILES['passport_img']['name'];
    $passportPath = "uploads/" . $passportName;

    move_uploaded_file(
        $_FILES['passport_img']['tmp_name'],
        $uploadDir . "/" . $passportName
    );
}

// Check if profile exists
$checkStmt = $conn->prepare("SELECT id FROM passengers WHERE user_id = ?");
$checkStmt->bind_param("i", $user_id);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    // Update existing profile
    $stmt = $conn->prepare("
        UPDATE passengers 
        SET full_name = COALESCE(?, full_name),
            account_balance = ?,
            photo = COALESCE(?, photo),
            passport_img = COALESCE(?, passport_img)
        WHERE user_id = ?
    ");
    $stmt->bind_param("sdssi", $full_name, $account, $photoPath, $passportPath, $user_id);
} else {
    // Insert new profile
    $stmt = $conn->prepare("
        INSERT INTO passengers (user_id, full_name, photo, passport_img, account_balance)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("isssd", $user_id, $full_name, $photoPath, $passportPath, $account);
}

if ($stmt->execute()) {
    jsonResponse(true, "Passenger profile completed", null);
}

jsonResponse(false, $stmt->error, null);
