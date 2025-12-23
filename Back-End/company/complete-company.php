<?php
header("Content-Type: application/json");
require "../configration/db.php";
require "../configration/helper-functions.php";

$data = $_POST;
$user_id = isset($data['user_id']) ? intval($data['user_id']) : null;

if (!$user_id) {
    echo json_encode([
        "success" => false,
        "message" => "User ID required"
    ]);
    exit();
}

$company_name = isset($data['company_name']) ? trim($data['company_name']) : '';
$bio = $data['bio'] ?? '';
$address = $data['address'] ?? '';
$location = $data['location'] ?? '';
$account = isset($data['account_balance']) ? floatval($data['account_balance']) : 0;

if (!is_dir(__DIR__ . "/../uploads")) {
    mkdir(__DIR__ . "/../uploads", 0777, true);
}

$logoPath = null;

if (!empty($_FILES['logo']['name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
    $fileName = time() . "_" . basename($_FILES['logo']['name']);
    $uploadPath = __DIR__ . "/../uploads/" . $fileName;
    
    if (move_uploaded_file($_FILES['logo']['tmp_name'], $uploadPath)) {
        $logoPath = "uploads/" . $fileName;
    }
}

$companyNameColumn = 'company_name';
$colCheck = $conn->query("SHOW COLUMNS FROM companies LIKE 'company_name'");
if (!$colCheck || $colCheck->num_rows === 0) {
    $colCheckTypo = $conn->query("SHOW COLUMNS FROM companies LIKE 'comapny_name'");
    if ($colCheckTypo && $colCheckTypo->num_rows > 0) {
        $companyNameColumn = 'comapny_name';
    }
}

$checkStmt = $conn->prepare("SELECT id FROM companies WHERE user_id = ?");
$checkStmt->bind_param("i", $user_id);
$checkStmt->execute();
$checkStmt->store_result();

if ($checkStmt->num_rows > 0) {
        if (!empty($logoPath)) {
            $updateSql = "UPDATE companies SET {$companyNameColumn} = ?, bio = ?, address = ?, location = ?, logo = ?, account_balance = ? WHERE user_id = ?";
            $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("sssssdi", $company_name, $bio, $address, $location, $logoPath, $account, $user_id);
    } else {
            $updateSql = "UPDATE companies SET {$companyNameColumn} = ?, bio = ?, address = ?, location = ?, account_balance = ? WHERE user_id = ?";
            $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("ssssdi", $company_name, $bio, $address, $location, $account, $user_id);
    }

    if ($updateStmt->execute()) {
        echo json_encode([
            "success" => true,
            "message" => "Company profile updated"
        ]);
        exit();
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Failed to update profile: " . $updateStmt->error
        ]);
        exit();
    }
} else {
        $insertSql = "INSERT INTO companies (user_id, {$companyNameColumn}, bio, address, location, logo, account_balance) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $insertStmt = $conn->prepare($insertSql);

    $insertStmt->bind_param("isssssd", $user_id, $company_name, $bio, $address, $location, $logoPath, $account);

    if ($insertStmt->execute()) {
        echo json_encode([
            "success" => true,
            "message" => "Company profile completed"
        ]);
        exit();
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Failed to create profile: " . $insertStmt->error
        ]);
        exit();
    }
}


 