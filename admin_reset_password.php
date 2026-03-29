<?php
require_once "db.php";

header("Content-Type: application/json; charset=UTF-8");

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid JSON body"
    ]);
    exit();
}

$email = trim($data["email"] ?? "");
$newPassword = $data["new_password"] ?? "";

if ($email === "" || $newPassword === "") {
    echo json_encode([
        "status" => "error",
        "message" => "Email and new password are required"
    ]);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        "status" => "error",
        "message" => "Enter a valid email"
    ]);
    exit();
}

$hasUpper = preg_match('/[A-Z]/', $newPassword);
$hasLower = preg_match('/[a-z]/', $newPassword);
$hasDigit = preg_match('/[0-9]/', $newPassword);
$hasSpecial = preg_match('/[^A-Za-z0-9]/', $newPassword);
$hasLength = strlen($newPassword) >= 8;

if (!($hasUpper && $hasLower && $hasDigit && $hasSpecial && $hasLength)) {
    echo json_encode([
        "status" => "error",
        "message" => "Password does not meet required format"
    ]);
    exit();
}

$checkReset = $conn->prepare("
    SELECT is_verified
    FROM password_resets
    WHERE email = ? AND user_type = 'admin'
    LIMIT 1
");
$checkReset->bind_param("s", $email);
$checkReset->execute();
$result = $checkReset->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "status" => "error",
        "message" => "No verified reset request found"
    ]);
    $checkReset->close();
    $conn->close();
    exit();
}

$row = $result->fetch_assoc();
$checkReset->close();

if ((int)$row["is_verified"] !== 1) {
    echo json_encode([
        "status" => "error",
        "message" => "OTP verification required before password reset"
    ]);
    $conn->close();
    exit();
}

$checkAdmin = $conn->prepare("SELECT id FROM admins WHERE email = ? LIMIT 1");
$checkAdmin->bind_param("s", $email);
$checkAdmin->execute();
$adminResult = $checkAdmin->get_result();

if ($adminResult->num_rows === 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Admin account not found"
    ]);
    $checkAdmin->close();
    $conn->close();
    exit();
}
$checkAdmin->close();

$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

$updateAdmin = $conn->prepare("UPDATE admins SET password = ? WHERE email = ?");
$updateAdmin->bind_param("ss", $hashedPassword, $email);

if (!$updateAdmin->execute()) {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to update password"
    ]);
    $updateAdmin->close();
    $conn->close();
    exit();
}
$updateAdmin->close();

$deleteReset = $conn->prepare("
    DELETE FROM password_resets
    WHERE email = ? AND user_type = 'admin'
");
$deleteReset->bind_param("s", $email);
$deleteReset->execute();
$deleteReset->close();

echo json_encode([
    "status" => "success",
    "message" => "Password reset successful"
]);

$conn->close();
?>