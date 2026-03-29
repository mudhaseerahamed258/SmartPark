<?php
require_once "db.php";

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

$user_id = isset($data["user_id"]) ? intval($data["user_id"]) : 0;

if ($user_id <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "user_id required"
    ]);
    exit();
}

/* Basic user info */
$stmt = $conn->prepare("
    SELECT id, full_name, email, phone_number, org_code, status, approval_seen
    FROM users
    WHERE id = ?
    LIMIT 1
");
$stmt->bind_param("i", $user_id);
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
$stmt->close();

/*
 If user has an active org_code, trust that as current active org.
 Otherwise check latest org request from user_organizations.
*/
$current_org_code = $user["org_code"];
$approval_status = $user["status"];

if (empty($current_org_code) || strtolower(trim($approval_status)) === "not_joined") {
    $stmt = $conn->prepare("
        SELECT org_code, status
        FROM user_organizations
        WHERE user_id = ?
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $latestResult = $stmt->get_result();

    if ($latestResult->num_rows > 0) {
        $latest = $latestResult->fetch_assoc();
        $current_org_code = $latest["org_code"];
        $approval_status = strtolower($latest["status"]);
    }

    $stmt->close();
}

echo json_encode([
    "status" => "success",
    "message" => "User status fetched successfully",
    "user" => [
        "id" => (int)$user["id"],
        "full_name" => $user["full_name"],
        "email" => $user["email"],
        "phone_number" => $user["phone_number"],
        "org_code" => $current_org_code,
        "approval_status" => $approval_status,
        "approval_seen" => (int)$user["approval_seen"]
    ]
]);

$conn->close();
?>