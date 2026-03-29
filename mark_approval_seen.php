<?php
require_once "db.php";

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

$user_id = intval($data["user_id"] ?? 0);

if ($user_id <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Valid user_id is required"
    ]);
    exit();
}

$stmt = $conn->prepare("UPDATE users SET approval_seen = 1 WHERE id = ?");
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "message" => "Approval marked as seen"
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to update approval_seen"
    ]);
}

$stmt->close();
$conn->close();
?>