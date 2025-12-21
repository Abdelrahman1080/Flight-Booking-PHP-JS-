<?php
header("Content-Type: application/json");
require "../configration/db.php";
require "../configration/helper-functions.php";

$data = $_POST;
$user_id = $data['user_id'] ?? '';

if (!$user_id) {
    jsonResponse(false, "User ID required");
}

$bio = $data['bio'] ?? '';
$address = $data['address'] ?? '';
$location = $data['location'] ?? '';
$account = $data['account_balance'] ?? 0;

// logo upload
if (!is_dir(__DIR__ . "/../uploads")) {
    mkdir(__DIR__ . "/../uploads", 0777, true);
}

$logoPath = null;

if (!empty($_FILES['logo']['name'])) {
    $fileName = time() . "_" . $_FILES['logo']['name'];
    $logoPath = "uploads/" . $fileName;

    move_uploaded_file(
        $_FILES['logo']['tmp_name'],
        __DIR__ . "/../uploads/" . $fileName
    );
}

$stmt = $conn->prepare("
    INSERT INTO companies (user_id, bio, address, location, logo, account_balance)
    VALUES (?, ?, ?, ?, ?, ?)
");

$stmt->bind_param("issssd", $user_id, $bio, $address, $location, $logoPath, $account);

if ($stmt->execute()) {
    jsonResponse(true, "Company profile completed");
}

jsonResponse(false, $stmt->error);


 