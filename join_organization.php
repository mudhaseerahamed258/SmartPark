<?php
require_once "db.php";

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

$user_id = $data["user_id"] ?? "";
$org_code = trim($data["org_code"] ?? "");

if ($user_id == "" || $org_code == "") {
    echo json_encode([
        "status" => "error",
        "message" => "user_id and org_code are required"
    ]);
    exit();
}

$stmt = $conn->prepare("SELECT id, org_name, org_code FROM organizations WHERE org_code = ?");
$stmt->bind_param("s", $org_code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid organization code"
    ]);
    exit();
}

$org = $result->fetch_assoc();
$stmt->close();

$stmt = $conn->prepare("UPDATE users SET org_code = ?, status = 'pending' WHERE id = ?");
$stmt->bind_param("si", $org_code, $user_id);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "message" => "Join request submitted successfully"
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to submit join request"
    ]);
}

$stmt->close();
$conn->close();
?>