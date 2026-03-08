<?php
require_once "db.php";

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

$admin_id = trim($data["admin_id"] ?? "");
$user_id = intval($data["user_id"] ?? 0);

if ($admin_id === "" || $user_id <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "admin_id and user_id are required"
    ]);
    exit();
}

/* Get admin org_code using string admin_id */
$stmt = $conn->prepare("SELECT org_code FROM organizations WHERE admin_id = ? LIMIT 1");
$stmt->bind_param("s", $admin_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Organization not found for this admin"
    ]);
    exit();
}

$org = $result->fetch_assoc();
$org_code = $org["org_code"];
$stmt->close();

/* Check user belongs to same org and is pending */
$stmt = $conn->prepare("
    SELECT id
    FROM users
    WHERE id = ? AND org_code = ? AND status = 'pending'
    LIMIT 1
");
$stmt->bind_param("is", $user_id, $org_code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Pending user not found in your organization"
    ]);
    exit();
}
$stmt->close();

/* Reject user */
$stmt = $conn->prepare("UPDATE users SET status = 'rejected' WHERE id = ?");
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "message" => "User rejected successfully"
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to reject user"
    ]);
}

$stmt->close();
$conn->close();
?>