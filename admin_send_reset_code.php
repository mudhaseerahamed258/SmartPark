<?php
require_once "db.php";
require_once "send_mail.php";

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

if ($email === "") {
    echo json_encode([
        "status" => "error",
        "message" => "Email is required"
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

$stmt = $conn->prepare("SELECT id, full_name, email FROM admins WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "status" => "error",
        "message" => "No admin account found with this email"
    ]);
    $stmt->close();
    $conn->close();
    exit();
}

$admin = $result->fetch_assoc();
$stmt->close();

$otp = str_pad((string) random_int(0, 999999), 6, "0", STR_PAD_LEFT);
$expiresAt = date("Y-m-d H:i:s", strtotime("+5 minutes"));

$upsert = $conn->prepare("
    INSERT INTO password_resets (email, user_type, otp, is_verified, expires_at)
    VALUES (?, 'admin', ?, 0, ?)
    ON DUPLICATE KEY UPDATE
        otp = VALUES(otp),
        is_verified = 0,
        expires_at = VALUES(expires_at),
        created_at = CURRENT_TIMESTAMP
");
$upsert->bind_param("sss", $email, $otp, $expiresAt);

if (!$upsert->execute()) {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to save OTP"
    ]);
    $upsert->close();
    $conn->close();
    exit();
}
$upsert->close();

$mailSent = sendOTPEmail($email, $otp);

if (!$mailSent) {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to send OTP email"
    ]);
    $conn->close();
    exit();
}

echo json_encode([
    "status" => "success",
    "message" => "OTP sent to your email"
]);

$conn->close();
?>