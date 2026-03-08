<?php
require_once "db.php";

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

$admin_id = trim($data["admin_id"] ?? "");

if ($admin_id === "") {
    echo json_encode([
        "status" => "error",
        "message" => "admin_id required"
    ]);
    exit();
}

/* Get org_code for this admin using string admin_id */
$stmt = $conn->prepare("SELECT org_code FROM organizations WHERE admin_id = ? LIMIT 1");
$stmt->bind_param("s", $admin_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "status" => "error",
        "message" => "organization not found"
    ]);
    exit();
}

$row = $result->fetch_assoc();
$org_code = $row["org_code"];

/* Get rejected users only for this organization */
$stmt = $conn->prepare("
    SELECT id, full_name, email, phone_number, status
    FROM users
    WHERE org_code = ? AND status = 'rejected'
");
$stmt->bind_param("s", $org_code);
$stmt->execute();
$result = $stmt->get_result();

$users = [];

while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

echo json_encode([
    "status" => "success",
    "users" => $users
]);

$conn->close();
?>