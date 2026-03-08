<?php
require_once "db.php";

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid JSON body"
    ]);
    exit();
}

$admin_id         = trim($data["admin_id"] ?? "");
$full_name        = trim($data["full_name"] ?? "");
$email            = trim($data["email"] ?? "");
$phone_number     = trim($data["phone_number"] ?? "");
$org_name         = trim($data["org_name"] ?? "");
$password         = $data["password"] ?? "";
$confirm_password = $data["confirm_password"] ?? "";

if (
    $admin_id === "" ||
    $full_name === "" ||
    $email === "" ||
    $phone_number === "" ||
    $org_name === "" ||
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

if (!preg_match('/^[0-9]{10,15}$/', $phone_number)) {
    echo json_encode([
        "status" => "error",
        "message" => "Phone number must be 10 to 15 digits"
    ]);
    exit();
}

if (strlen($admin_id) < 4) {
    echo json_encode([
        "status" => "error",
        "message" => "Admin ID must be at least 4 characters"
    ]);
    exit();
}

if (strlen($password) < 8) {
    echo json_encode([
        "status" => "error",
        "message" => "Password must be at least 8 characters"
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

# Check duplicate email
$stmt = $conn->prepare("SELECT id FROM admins WHERE email = ?");
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

# Check duplicate phone
$stmt = $conn->prepare("SELECT id FROM admins WHERE phone_number = ?");
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

# Check duplicate admin_id
$stmt = $conn->prepare("SELECT id FROM admins WHERE admin_id = ?");
$stmt->bind_param("s", $admin_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->close();
    echo json_encode([
        "status" => "error",
        "message" => "Admin ID already exists"
    ]);
    exit();
}
$stmt->close();

$hashed_password = password_hash($password, PASSWORD_BCRYPT);

$stmt = $conn->prepare("
    INSERT INTO admins (admin_id, full_name, email, phone_number, org_name, password)
    VALUES (?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "ssssss",
    $admin_id,
    $full_name,
    $email,
    $phone_number,
    $org_name,
    $hashed_password
);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "message" => "Admin registered successfully"
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Admin registration failed"
    ]);
}

$stmt->close();
$conn->close();
?>