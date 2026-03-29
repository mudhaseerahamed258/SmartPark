<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once "db.php";

function sendJson($data) {
    if (ob_get_length()) {
        ob_clean();
    }
    echo json_encode($data);
    exit();
}

$user_id = isset($_GET["user_id"]) ? intval($_GET["user_id"]) : 0;
$org_code = strtoupper(trim($_GET["org_code"] ?? ""));

if ($user_id <= 0) {
    sendJson([
        "status" => "error",
        "message" => "Valid user_id is required"
    ]);
}

if ($org_code === "") {
    sendJson([
        "status" => "error",
        "message" => "Valid org_code is required"
    ]);
}

/* Check user exists */
$userStmt = $conn->prepare("
    SELECT id
    FROM users
    WHERE id = ?
    LIMIT 1
");

if (!$userStmt) {
    sendJson([
        "status" => "error",
        "message" => "Failed to prepare user query"
    ]);
}

$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$userResult = $userStmt->get_result();

if (!$userResult || $userResult->num_rows === 0) {
    sendJson([
        "status" => "error",
        "message" => "User not found"
    ]);
}
$userStmt->close();

/* Check user is approved for this organization */
$membershipStmt = $conn->prepare("
    SELECT id
    FROM user_organizations
    WHERE user_id = ?
      AND org_code = ?
      AND status = 'APPROVED'
    LIMIT 1
");

if (!$membershipStmt) {
    sendJson([
        "status" => "error",
        "message" => "Failed to prepare membership query"
    ]);
}

$membershipStmt->bind_param("is", $user_id, $org_code);
$membershipStmt->execute();
$membershipResult = $membershipStmt->get_result();

if (!$membershipResult || $membershipResult->num_rows === 0) {
    sendJson([
        "status" => "success",
        "org_code" => $org_code,
        "vehicles" => []
    ]);
}
$membershipStmt->close();

/* Load only vehicles for selected org */
$stmt = $conn->prepare("
    SELECT
        id,
        user_id,
        org_code,
        vehicle_number,
        vehicle_type,
        parking_slot,
        pillar_number,
        landmark,
        floor,
        zone_name,
        is_visitor,
        updated_at
    FROM user_vehicles
    WHERE user_id = ?
      AND org_code = ?
      AND status = 'ACTIVE'
    ORDER BY id DESC
");

if (!$stmt) {
    sendJson([
        "status" => "error",
        "message" => "Failed to prepare vehicle query"
    ]);
}

$stmt->bind_param("is", $user_id, $org_code);
$stmt->execute();
$result = $stmt->get_result();

$vehicles = [];

while ($row = $result->fetch_assoc()) {
    $vehicles[] = [
        "id" => strval($row["id"]),
        "number" => $row["vehicle_number"],
        "type" => $row["vehicle_type"],
        "parkingSlot" => $row["parking_slot"],
        "pillarNumber" => $row["pillar_number"],
        "landmark" => $row["landmark"],
        "floor" => $row["floor"],
        "zone" => $row["zone_name"],
        "isVisitor" => intval($row["is_visitor"]) === 1,
        "lastUpdated" => $row["updated_at"]
    ];
}

$stmt->close();
$conn->close();

sendJson([
    "status" => "success",
    "org_code" => $org_code,
    "vehicles" => $vehicles
]);
?>