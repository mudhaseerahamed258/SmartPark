<?php
require_once "db.php";

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

$data = json_decode(file_get_contents("php://input"), true);

$admin_id = trim($data["admin_id"] ?? "");

if ($admin_id === "") {
    echo json_encode([
        "status" => "error",
        "message" => "admin_id required"
    ]);
    exit();
}

/* Get org_code for this admin */
$orgStmt = $conn->prepare("
    SELECT id, org_code
    FROM organizations
    WHERE admin_id = ?
    LIMIT 1
");

if (!$orgStmt) {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to prepare organization query"
    ]);
    exit();
}

$orgStmt->bind_param("s", $admin_id);
$orgStmt->execute();
$orgResult = $orgStmt->get_result();

if ($orgResult->num_rows === 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Organization not found"
    ]);
    exit();
}

$orgRow = $orgResult->fetch_assoc();
$org_code = trim($orgRow["org_code"] ?? "");

if ($org_code === "") {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid organization org_code"
    ]);
    exit();
}

/* Get approved users for this organization from user_organizations */
$userStmt = $conn->prepare("
    SELECT
        u.id,
        u.full_name,
        u.email,
        u.phone_number,
        'approved' AS status,
        COUNT(v.id) AS vehicle_count
    FROM user_organizations uo
    INNER JOIN users u
        ON u.id = uo.user_id
    LEFT JOIN user_vehicles v
        ON u.id = v.user_id
        AND v.org_code = uo.org_code
        AND v.status = 'ACTIVE'
    WHERE uo.org_code = ?
      AND uo.status = 'APPROVED'
    GROUP BY u.id, u.full_name, u.email, u.phone_number
    ORDER BY u.id DESC
");

if (!$userStmt) {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to prepare users query"
    ]);
    exit();
}

$userStmt->bind_param("s", $org_code);
$userStmt->execute();
$userResult = $userStmt->get_result();

$users = [];

while ($row = $userResult->fetch_assoc()) {
    $users[] = [
        "id" => strval($row["id"]),
        "full_name" => $row["full_name"],
        "email" => $row["email"],
        "phone_number" => $row["phone_number"],
        "status" => $row["status"],
        "vehicle_count" => intval($row["vehicle_count"])
    ];
}

echo json_encode([
    "status" => "success",
    "org_code" => $org_code,
    "users" => $users
]);

$userStmt->close();
$orgStmt->close();
$conn->close();
?>