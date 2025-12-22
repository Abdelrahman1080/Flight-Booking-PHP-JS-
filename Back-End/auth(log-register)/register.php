<?php
session_start();
header("Content-Type: application/json");
require "../configration/db.php";
require "../configration/helper-functions.php";

$data = json_decode(file_get_contents("php://input"), true);

$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';
$tel = $data['tel'] ?? '';
$user_type = $data['user_type'] ?? '';

if (!$name || !$email || !$password || !$user_type) {
    jsonResponse(false, "Missing required fields", null);
}

if (!in_array($user_type, ['company', 'passenger'])) {
    jsonResponse(false, "Invalid user type", null);
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// check email exists
$check = $conn->prepare("SELECT id FROM users WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    jsonResponse(false, "Email already exists", null);
}

$stmt = $conn->prepare("
    INSERT INTO users (name, email, password, tel, user_type)
    VALUES (?, ?, ?, ?, ?)
");

$stmt->bind_param("sssss", $name, $email, $hashedPassword, $tel, $user_type);

if ($stmt->execute()) {
    $userId = $stmt->insert_id;
    
    // Set session
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_type'] = $user_type;
    
    // AUTO-CREATE passenger or company record immediately
    if ($user_type === 'company') {
        // Create empty company record
        $companyStmt = $conn->prepare("
            INSERT INTO companies (user_id, account_balance)
            VALUES (?, 0)
        ");
        $companyStmt->bind_param("i", $userId);
        $companyStmt->execute();
        $companyStmt->close();
        
        echo json_encode([
            "success" => true,
            "message" => "Registered successfully",
            "data" => [
                "user_id" => $userId,
                "name" => $name,
                "email" => $email,
                "tel" => $tel,
                "user_type" => $user_type
            ]
        ]);
    } else {
        // Create empty passenger record
        $passengerStmt = $conn->prepare("
            INSERT INTO passengers (user_id, account_balance)
            VALUES (?, 0)
        ");
        $passengerStmt->bind_param("i", $userId);
        $passengerStmt->execute();
        $passengerStmt->close();
        
        echo json_encode([
            "success" => true,
            "message" => "Registered successfully",
            "data" => [
                "user_id" => $userId,
                "name" => $name,
                "email" => $email,
                "tel" => $tel,
                "user_type" => $user_type
            ]
        ]);
    }
    exit();
}

echo json_encode([
    "success" => false,
    "message" => "Registration failed",
    "data" => null
]);
exit();
