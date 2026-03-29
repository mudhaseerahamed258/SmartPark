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

$admin_id = trim($data["admin_id"] ?? "");
$password = $data["password"] ?? "";

if ($admin_id === "" || $password === "") {
    echo json_encode([
        "status" => "error",
        "message" => "Admin ID and password are required"
    ]);
    exit();
}

$stmt = $conn->prepare("
    SELECT 
        a.id,
        a.full_name,
        a.email,
        a.phone_number,
        a.org_name,
        a.admin_id,
        a.password,
        o.id AS organization_id,
        o.org_code
    FROM admins a
    LEFT JOIN organizations o ON a.admin_id = o.admin_id
    WHERE a.admin_id = ?
    LIMIT 1
");
$stmt->bind_param("s", $admin_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Admin not found"
    ]);
    exit();
}

$admin = $result->fetch_assoc();

if (!password_verify($password, $admin["password"])) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid password"
    ]);
    exit();
}

echo json_encode([
    "status" => "success",
    "message" => "Admin login successful",
    "user" => [
        "id" => (int)$admin["id"],
        "full_name" => $admin["full_name"],
        "email" => $admin["email"],
        "phone_number" => $admin["phone_number"],
        "org_code" => $admin["org_code"] ?? "",
        "approval_status" => "approved"
    ],
    "admin_id" => $admin["admin_id"],
    "org_name" => $admin["org_name"],
    "organization_id" => isset($admin["organization_id"]) ? (int)$admin["organization_id"] : 0
]);

$stmt->close();
$conn->close();
?>