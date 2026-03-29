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
$otp   = trim($data["otp"] ?? "");

if ($email === "" || $otp === "") {
    echo json_encode([
        "status" => "error",
        "message" => "Email and OTP are required"
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

if (!preg_match('/^\d{6}$/', $otp)) {
    echo json_encode([
        "status" => "error",
        "message" => "Enter a valid 6-digit OTP"
    ]);
    exit();
}

$stmt = $conn->prepare("
    SELECT id, otp, expires_at
    FROM password_resets
    WHERE email = ? AND user_type = 'admin'
    LIMIT 1
");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "status" => "error",
        "message" => "No OTP request found for this email"
    ]);
    $stmt->close();
    $conn->close();
    exit();
}

$row = $result->fetch_assoc();
$stmt->close();

if ($row["otp"] !== $otp) {
    echo json_encode([
        "status" => "error",
        "message" => "Incorrect OTP"
    ]);
    $conn->close();
    exit();
}

if (strtotime($row["expires_at"]) < time()) {
    echo json_encode([
        "status" => "error",
        "message" => "OTP has expired"
    ]);
    $conn->close();
    exit();
}

$update = $conn->prepare("
    UPDATE password_resets
    SET is_verified = 1
    WHERE email = ? AND user_type = 'admin'
");
$update->bind_param("s", $email);

if (!$update->execute()) {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to verify OTP"
    ]);
    $update->close();
    $conn->close();
    exit();
}

$update->close();

echo json_encode([
    "status" => "success",
    "message" => "OTP verified successfully"
]);

$conn->close();
?>