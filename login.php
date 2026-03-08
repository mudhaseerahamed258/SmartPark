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

$identifier = trim($data["identifier"] ?? "");
$password   = $data["password"] ?? "";

if ($identifier === "" || $password === "") {
    echo json_encode([
        "status" => "error",
        "message" => "Email/phone and password are required"
    ]);
    exit();
}

$stmt = $conn->prepare(
    "SELECT id, full_name, email, phone_number, org_code, status, password
     FROM users
     WHERE email = ? OR phone_number = ?
     LIMIT 1"
);
$stmt->bind_param("ss", $identifier, $identifier);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "status" => "error",
        "message" => "User not found"
    ]);
    exit();
}

$user = $result->fetch_assoc();

if (!password_verify($password, $user["password"])) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid password"
    ]);
    exit();
}

echo json_encode([
    "status" => "success",
    "message" => "Login successful",
    "user" => [
        "id" => (int)$user["id"],
        "full_name" => $user["full_name"],
        "email" => $user["email"],
        "phone_number" => $user["phone_number"],
        "org_code" => $user["org_code"],
        "approval_status" => $user["status"]
    ]
]);

$stmt->close();
$conn->close();
?>