<?php
require_once "db.php";

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

$user_id = $data["user_id"] ?? "";

if ($user_id == "") {
    echo json_encode([
        "status" => "error",
        "message" => "user_id required"
    ]);
    exit();
}

$stmt = $conn->prepare("SELECT id, full_name, email, phone_number, org_code, status FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo json_encode([
        "status" => "error",
        "message" => "User not found"
    ]);
    exit();
}

$user = $result->fetch_assoc();

echo json_encode([
    "status" => "success",
    "message" => "User status fetched successfully",
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