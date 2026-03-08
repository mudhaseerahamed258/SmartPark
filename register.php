<?php
require_once "db.php";

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid JSON body"
    ]);
    exit();
}

$full_name        = trim($data["full_name"] ?? "");
$email            = trim($data["email"] ?? "");
$phone_number     = trim($data["phone_number"] ?? "");
$password         = $data["password"] ?? "";
$confirm_password = $data["confirm_password"] ?? "";

if (
    $full_name === "" ||
    $email === "" ||
    $phone_number === "" ||
    $password === "" ||
    $confirm_password === ""
) {
    echo json_encode([
        "status" => "error",
        "message" => "All fields are required"
    ]);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid email format"
    ]);
    exit();
}

if (!preg_match('/^[0-9]{10}$/', $phone_number)) {
    echo json_encode([
        "status" => "error",
        "message" => "Phone number must be 10 digits"
    ]);
    exit();
}

if (strlen($password) < 6) {
    echo json_encode([
        "status" => "error",
        "message" => "Password must be at least 6 characters"
    ]);
    exit();
}

if ($password !== $confirm_password) {
    echo json_encode([
        "status" => "error",
        "message" => "Passwords do not match"
    ]);
    exit();
}

$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->close();
    echo json_encode([
        "status" => "error",
        "message" => "Email already registered"
    ]);
    exit();
}
$stmt->close();

$stmt = $conn->prepare("SELECT id FROM users WHERE phone_number = ?");
$stmt->bind_param("s", $phone_number);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->close();
    echo json_encode([
        "status" => "error",
        "message" => "Phone number already registered"
    ]);
    exit();
}
$stmt->close();

$hashed_password = password_hash($password, PASSWORD_BCRYPT);

$stmt = $conn->prepare("INSERT INTO users (full_name, email, phone_number, password, org_code, status) VALUES (?, ?, ?, ?, NULL, 'not_joined')");
$stmt->bind_param("ssss", $full_name, $email, $phone_number, $hashed_password);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "message" => "User registered successfully",
        "user_id" => $stmt->insert_id
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Registration failed"
    ]);
}

$stmt->close();
$conn->close();
?>