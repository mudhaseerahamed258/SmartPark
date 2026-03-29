<?php
require_once "db.php";
require_once "vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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

$stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    $stmt->close();
    echo json_encode([
        "status" => "error",
        "message" => "User not found"
    ]);
    exit();
}
$stmt->close();

$otp = str_pad((string) random_int(0, 999999), 6, "0", STR_PAD_LEFT);
$expires = date("Y-m-d H:i:s", strtotime("+5 minutes"));

$stmt = $conn->prepare("
    INSERT INTO password_resets (email, user_type, otp, is_verified, expires_at)
    VALUES (?, 'user', ?, 0, ?)
    ON DUPLICATE KEY UPDATE
        otp = VALUES(otp),
        is_verified = 0,
        expires_at = VALUES(expires_at),
        created_at = CURRENT_TIMESTAMP
");
$stmt->bind_param("sss", $email, $otp, $expires);

if (!$stmt->execute()) {
    $stmt->close();
    echo json_encode([
        "status" => "error",
        "message" => "Failed to save OTP"
    ]);
    exit();
}
$stmt->close();

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'skmudhaseerahamed2580@gmail.com';
    $mail->Password = 'bgda svav hnct aeul';
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('skmudhaseerahamed2580@gmail.com', 'SmartPark Connect');
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = 'Password Reset OTP';
    $mail->Body = "
        <h3>SmartPark Connect</h3>
        <p>Your OTP for password reset is:</p>
        <h2>$otp</h2>
        <p>This OTP will expire in 5 minutes.</p>
    ";

    $mail->send();

    echo json_encode([
        "status" => "success",
        "message" => "OTP sent to your email"
    ]);
} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to send OTP email"
    ]);
}

$conn->close();
?>