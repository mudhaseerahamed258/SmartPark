<?php
require_once "db.php";

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

$user_id = intval($data["user_id"] ?? 0);

if ($user_id <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "user_id is required"
    ]);
    exit();
}

$stmt = $conn->prepare("
    UPDATE users
    SET status = 'not_joined', org_code = NULL
    WHERE id = ? AND status = 'rejected'
");
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "message" => "User reset successfully"
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to reset user"
    ]);
}

$stmt->close();
$conn->close();
?>