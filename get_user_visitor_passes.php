<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

include "db.php";

$user_id = isset($_GET["user_id"]) ? intval($_GET["user_id"]) : 0;

if ($user_id <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Valid user_id is required"
    ]);
    exit();
}

$stmt = $conn->prepare("
    SELECT
        id,
        organization_id,
        org_code,
        user_id,
        vehicle_number,
        visitor_name,
        purpose,
        duration_hours,
        building,
        floor,
        slot_number,
        entry_time,
        exit_time,
        pass_code,
        status,
        created_at
    FROM visitor_passes
    WHERE user_id = ?
    ORDER BY id DESC
");

if (!$stmt) {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to prepare query"
    ]);
    exit();
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$passes = [];

while ($row = $result->fetch_assoc()) {
    $passes[] = [
        "id" => strval($row["id"]),
        "vehicleNumber" => $row["vehicle_number"],
        "visitorName" => $row["visitor_name"],
        "purpose" => $row["purpose"],
        "durationHours" => intval($row["duration_hours"]),
        "building" => $row["building"],
        "floor" => $row["floor"],
        "slotNumber" => $row["slot_number"],
        "entryTime" => $row["entry_time"],
        "exitTime" => $row["exit_time"],
        "passCode" => $row["pass_code"],
        "status" => strtoupper($row["status"])
    ];
}

echo json_encode([
    "status" => "success",
    "passes" => $passes
]);

$stmt->close();
$conn->close();
?>