<?php
header("Content-Type: application/json");
require "../configration/db.php";
require "../configration/helper-functions.php";

$data = $_POST;
$user_id = $data['user_id'] ?? '';
$account = $data['account_balance'] ?? 0;

if (!$user_id) {
    jsonResponse(false, "User ID required");
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

$stmt = $conn->prepare("
    INSERT INTO passengers (user_id, photo, passport_img, account_balance)
    VALUES (?, ?, ?, ?)
");

$stmt->bind_param("issd", $user_id, $photoPath, $passportPath, $account);

if ($stmt->execute()) {
    jsonResponse(true, "Passenger profile completed");
}

jsonResponse(false, $stmt->error);
