<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

include "db.php";

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid input data"
    ]);
    exit();
}

$user_id = isset($data["user_id"]) ? intval($data["user_id"]) : 0;
$vehicle_number = trim($data["vehicle_number"] ?? "");
$visitor_name = trim($data["visitor_name"] ?? "");
$visitor_phone_number = trim($data["visitor_phone_number"] ?? "");
$purpose = trim($data["purpose"] ?? "");
$duration_hours = isset($data["duration_hours"]) ? intval($data["duration_hours"]) : 0;
$building = trim($data["building"] ?? "");
$floor = trim($data["floor"] ?? "");
$slot_number = trim($data["slot_number"] ?? "");
$entry_time = trim($data["entry_time"] ?? "");
$exit_time = trim($data["exit_time"] ?? "");
$pass_code = trim($data["pass_code"] ?? "");
$status = trim($data["status"] ?? "ACTIVE");

if (
    $user_id <= 0 ||
    $vehicle_number === "" ||
    $visitor_name === "" ||
    $visitor_phone_number === "" ||
    $purpose === "" ||
    $duration_hours <= 0 ||
    $building === "" ||
    $floor === "" ||
    $slot_number === "" ||
    $entry_time === "" ||
    $exit_time === "" ||
    $pass_code === ""
) {
    echo json_encode([
        "status" => "error",
        "message" => "All required fields must be provided"
    ]);
    exit();
}

$userStmt = $conn->prepare("SELECT org_code, full_name, phone_number FROM users WHERE id = ?");
if (!$userStmt) {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to prepare user query"
    ]);
    exit();
}

$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$userResult = $userStmt->get_result();

if ($userResult->num_rows === 0) {
    echo json_encode([
        "status" => "error",
        "message" => "User not found"
    ]);
    exit();
}

$userRow = $userResult->fetch_assoc();
$org_code = trim($userRow["org_code"] ?? "");

if ($org_code === "") {
    echo json_encode([
        "status" => "error",
        "message" => "User is not joined to any organization"
    ]);
    exit();
}

$orgStmt = $conn->prepare("SELECT id FROM organizations WHERE org_code = ?");
if (!$orgStmt) {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to prepare organization query"
    ]);
    exit();
}

$orgStmt->bind_param("s", $org_code);
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
$organization_id = intval($orgRow["id"]);

$insertStmt = $conn->prepare("
    INSERT INTO visitor_passes (
        organization_id,
        org_code,
        user_id,
        vehicle_number,
        visitor_name,
        visitor_phone_number,
        purpose,
        duration_hours,
        building,
        floor,
        slot_number,
        entry_time,
        exit_time,
        pass_code,
        status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

if (!$insertStmt) {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to prepare insert query"
    ]);
    exit();
}

$insertStmt->bind_param(
    "isissssisssssss",
    $organization_id,
    $org_code,
    $user_id,
    $vehicle_number,
    $visitor_name,
    $visitor_phone_number,
    $purpose,
    $duration_hours,
    $building,
    $floor,
    $slot_number,
    $entry_time,
    $exit_time,
    $pass_code,
    $status
);

if ($insertStmt->execute()) {
    echo json_encode([
        "status" => "success",
        "message" => "Visitor pass created successfully",
        "visitor_pass_id" => $insertStmt->insert_id,
        "organization_id" => $organization_id,
        "org_code" => $org_code
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to create visitor pass"
    ]);
}

$insertStmt->close();
$orgStmt->close();
$userStmt->close();
$conn->close();