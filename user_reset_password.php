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
$new_password = $data["new_password"] ?? "";

if ($email === "" || $new_password === "") {
    echo json_encode([
        "status" => "error",
        "message" => "Missing fields"
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

$hasUpper = preg_match('/[A-Z]/', $new_password);
$hasLower = preg_match('/[a-z]/', $new_password);
$hasDigit = preg_match('/[0-9]/', $new_password);
$hasSpecial = preg_match('/[^A-Za-z0-9]/', $new_password);
$hasLength = strlen($new_password) >= 8;

if (!($hasUpper && $hasLower && $hasDigit && $hasSpecial && $hasLength)) {
    echo json_encode([
        "status" => "error",
        "message" => "Password does not meet required format"
    ]);
    exit();
}

$stmt = $conn->prepare("
    SELECT id, is_verified
    FROM password_resets
    WHERE email = ? AND user_type = 'user'
    LIMIT 1
");
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    $stmt->close();
    echo json_encode([
        "status" => "error",
        "message" => "No verified reset request found"
    ]);
    exit();
}

$row = $res->fetch_assoc();
$stmt->close();

if ((int)$row["is_verified"] !== 1) {
    echo json_encode([
        "status" => "error",
        "message" => "OTP not verified"
    ]);
    exit();
}

$checkUser = $conn->prepare("SELECT id, password FROM users WHERE email = ? LIMIT 1");
$checkUser->bind_param("s", $email);
$checkUser->execute();
$userRes = $checkUser->get_result();

if ($userRes->num_rows === 0) {
    $checkUser->close();
    echo json_encode([
        "status" => "error",
        "message" => "User account not found"
    ]);
    exit();
}

$userRow = $userRes->fetch_assoc();
$checkUser->close();

$hash = password_hash($new_password, PASSWORD_DEFAULT);

$updateUser = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
$updateUser->bind_param("ss", $hash, $email);

if (!$updateUser->execute()) {
    $updateUser->close();
    echo json_encode([
        "status" => "error",
        "message" => "Failed to update password"
    ]);
    exit();
}

if ($updateUser->affected_rows <= 0) {
    $updateUser->close();
    echo json_encode([
        "status" => "error",
        "message" => "Password not updated. Email may not match any user."
    ]);
    exit();
}

$updateUser->close();

$deleteReset = $conn->prepare("
    DELETE FROM password_resets
    WHERE email = ? AND user_type = 'user'
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