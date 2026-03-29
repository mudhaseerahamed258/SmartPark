<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once "db.php";

function sendJson($data) {
    if (ob_get_length()) {
        ob_clean();
    }
    echo json_encode($data);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

if (!is_array($data)) {
    sendJson([
        "status" => "error",
        "message" => "Invalid request data"
    ]);
}

$vehicle_id = isset($data["vehicle_id"]) ? intval($data["vehicle_id"]) : 0;
$user_id = isset($data["user_id"]) ? intval($data["user_id"]) : 0;
$parking_slot = trim($data["parking_slot"] ?? "N/A");
$pillar_number = trim($data["pillar_number"] ?? "N/A");
$landmark = trim($data["landmark"] ?? "N/A");
$floor = trim($data["floor"] ?? "N/A");
$zone_name = trim($data["zone_name"] ?? "N/A");

if ($vehicle_id <= 0 || $user_id <= 0) {
    sendJson([
        "status" => "error",
        "message" => "vehicle_id and user_id required"
    ]);
}

$userStmt = $conn->prepare("SELECT org_code FROM users WHERE id = ? LIMIT 1");

if (!$userStmt) {
    sendJson([
        "status" => "error",
        "message" => "User query failed"
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

$userRow = $userResult->fetch_assoc();
$org_code = trim($userRow["org_code"] ?? "");

$update = $conn->prepare("
    UPDATE user_vehicles
    SET
        parking_slot = ?,
        pillar_number = ?,
        landmark = ?,
        floor = ?,
        zone_name = ?
    WHERE id = ?
      AND user_id = ?
      AND org_code = ?
      AND status = 'ACTIVE'
");

if (!$update) {
    sendJson([
        "status" => "error",
        "message" => "Update query failed"
    ]);
}

$update->bind_param(
    "sssssiss",
    $parking_slot,
    $pillar_number,
    $landmark,
    $floor,
    $zone_name,
    $vehicle_id,
    $user_id,
    $org_code
);

if ($update->execute()) {
    sendJson([
        "status" => "success",
        "message" => "Vehicle updated successfully"
    ]);
} else {
    sendJson([
        "status" => "error",
        "message" => "Vehicle update failed"
    ]);
}