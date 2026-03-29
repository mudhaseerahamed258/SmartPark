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
        "message" => "Invalid input data"
    ]);
}

$user_id = isset($data["user_id"]) ? intval($data["user_id"]) : 0;
$org_code = strtoupper(trim($data["org_code"] ?? ""));
$vehicle_number = strtoupper(trim($data["vehicle_number"] ?? ""));
$vehicle_type = trim($data["vehicle_type"] ?? "");
$parking_slot = trim($data["parking_slot"] ?? "N/A");
$pillar_number = trim($data["pillar_number"] ?? "N/A");
$landmark = trim($data["landmark"] ?? "N/A");
$floor = trim($data["floor"] ?? "N/A");
$zone_name = trim($data["zone_name"] ?? "N/A");
$is_visitor = isset($data["is_visitor"]) ? intval($data["is_visitor"]) : 0;

if ($user_id <= 0 || $org_code === "" || $vehicle_number === "" || $vehicle_type === "") {
    sendJson([
        "status" => "error",
        "message" => "user_id, org_code, vehicle_number and vehicle_type are required"
    ]);
}

/* Check user exists */
$userStmt = $conn->prepare("
    SELECT id, status
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

$userRow = $userResult->fetch_assoc();
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
        "status" => "error",
        "message" => "You are not approved for this organization"
    ]);
}
$membershipStmt->close();

/* Ensure organization exists */
$orgStmt = $conn->prepare("SELECT id FROM organizations WHERE org_code = ? LIMIT 1");

if (!$orgStmt) {
    sendJson([
        "status" => "error",
        "message" => "Failed to prepare organization query"
    ]);
}

$orgStmt->bind_param("s", $org_code);
$orgStmt->execute();
$orgResult = $orgStmt->get_result();

if (!$orgResult || $orgResult->num_rows === 0) {
    sendJson([
        "status" => "error",
        "message" => "Organization not found"
    ]);
}

$orgRow = $orgResult->fetch_assoc();
$organization_id = intval($orgRow["id"]);
$orgStmt->close();

/* Prevent duplicate active vehicle number for same user in same org */
$checkStmt = $conn->prepare("
    SELECT id
    FROM user_vehicles
    WHERE user_id = ?
      AND org_code = ?
      AND vehicle_number = ?
      AND status = 'ACTIVE'
    LIMIT 1
");

if (!$checkStmt) {
    sendJson([
        "status" => "error",
        "message" => "Failed to prepare duplicate check query"
    ]);
}

$checkStmt->bind_param("iss", $user_id, $org_code, $vehicle_number);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult && $checkResult->num_rows > 0) {
    sendJson([
        "status" => "error",
        "message" => "Vehicle already exists for this user in this organization"
    ]);
}
$checkStmt->close();

/* Optional max vehicle rule for this org */
$maxVehicles = 0;
$ruleStmt = $conn->prepare("
    SELECT max_vehicles_per_resident
    FROM organization_rules
    WHERE organization_id = ?
    ORDER BY id DESC
    LIMIT 1
");

if ($ruleStmt) {
    $ruleStmt->bind_param("i", $organization_id);
    $ruleStmt->execute();
    $ruleResult = $ruleStmt->get_result();

    if ($ruleResult && $ruleResult->num_rows > 0) {
        $ruleRow = $ruleResult->fetch_assoc();
        $maxVehicles = intval($ruleRow["max_vehicles_per_resident"] ?? 0);
    }
    $ruleStmt->close();
}

if ($maxVehicles > 0 && $is_visitor === 0) {
    $countStmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM user_vehicles
        WHERE user_id = ?
          AND org_code = ?
          AND is_visitor = 0
          AND status = 'ACTIVE'
    ");

    if ($countStmt) {
        $countStmt->bind_param("is", $user_id, $org_code);
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $countRow = $countResult ? $countResult->fetch_assoc() : ["total" => 0];
        $currentVehicleCount = intval($countRow["total"] ?? 0);
        $countStmt->close();

        if ($currentVehicleCount >= $maxVehicles) {
            sendJson([
                "status" => "error",
                "message" => "Maximum vehicle limit reached for this organization",
                "max_vehicles_per_resident" => $maxVehicles
            ]);
        }
    }
}

/* Insert vehicle into selected org */
$insertStmt = $conn->prepare("
    INSERT INTO user_vehicles (
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
        status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'ACTIVE')
");

if (!$insertStmt) {
    sendJson([
        "status" => "error",
        "message" => "Failed to prepare insert query"
    ]);
}

$insertStmt->bind_param(
    "issssssssi",
    $user_id,
    $org_code,
    $vehicle_number,
    $vehicle_type,
    $parking_slot,
    $pillar_number,
    $landmark,
    $floor,
    $zone_name,
    $is_visitor
);

if ($insertStmt->execute()) {
    sendJson([
        "status" => "success",
        "message" => "Vehicle added successfully",
        "vehicle_id" => $insertStmt->insert_id,
        "org_code" => $org_code
    ]);
} else {
    sendJson([
        "status" => "error",
        "message" => "Failed to add vehicle"
    ]);
}
?>