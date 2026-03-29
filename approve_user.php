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

/* Get admin org_code */
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

/* Check pending org request exists in user_organizations */
$stmt = $conn->prepare("
    SELECT id
    FROM user_organizations
    WHERE user_id = ? AND org_code = ? AND status = 'PENDING'
    LIMIT 1
");
$stmt->bind_param("is", $user_id, $org_code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Pending organization request not found for this user"
    ]);
    exit();
}
$stmt->close();

/* Approve in user_organizations */
$stmt = $conn->prepare("
    UPDATE user_organizations
    SET status = 'APPROVED'
    WHERE user_id = ? AND org_code = ?
");
$stmt->bind_param("is", $user_id, $org_code);

if (!$stmt->execute()) {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to approve user organization request"
    ]);
    $stmt->close();
    $conn->close();
    exit();
}
$stmt->close();

/* Check if user has active org already */
$stmt = $conn->prepare("SELECT org_code, status FROM users WHERE id = ? LIMIT 1");
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
$current_org_code = $user["org_code"];
$current_status = strtolower(trim($user["status"] ?? ""));
$stmt->close();

/*
 If user has no active org yet, make this approved org the active one.
 This handles first-time org approval flow.
*/
if (empty($current_org_code) || $current_status === "pending" || $current_status === "not_joined") {
    $stmt = $conn->prepare("
        UPDATE users
        SET org_code = ?, status = 'approved', approval_seen = 0
        WHERE id = ?
    ");
    $stmt->bind_param("si", $org_code, $user_id);

    if (!$stmt->execute()) {
        echo json_encode([
            "status" => "error",
            "message" => "Approved org request, but failed to update active organization"
        ]);
        $stmt->close();
        $conn->close();
        exit();
    }
    $stmt->close();
}

echo json_encode([
    "status" => "success",
    "message" => "User approved successfully"
]);

$conn->close();
?>