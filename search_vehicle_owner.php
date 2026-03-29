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

$vehicle_number = strtoupper(trim($data["vehicle_number"] ?? ""));
$org_code = trim($data["org_code"] ?? "");

if ($vehicle_number === "" || $org_code === "") {
    echo json_encode([
        "status" => "error",
        "message" => "Vehicle number and org code are required"
    ]);
    exit();
}

function maskPhone($phone) {
    $phone = trim($phone);
    if ($phone === "") {
        return "Not Available";
    }

    $digits = preg_replace('/\D/', '', $phone);

    if (strlen($digits) >= 4) {
        $last4 = substr($digits, -4);
        return "******" . $last4;
    }

    return "******";
}

/*
|--------------------------------------------------------------------------
| 1) Search Resident Vehicle
|--------------------------------------------------------------------------
*/
$residentSql = "SELECT 
                    uv.id,
                    uv.vehicle_number,
                    uv.vehicle_type,
                    uv.parking_slot,
                    uv.pillar_number,
                    uv.landmark,
                    uv.floor,
                    uv.zone_name,
                    u.full_name,
                    u.phone_number
                FROM user_vehicles uv
                INNER JOIN users u ON uv.user_id = u.id
                WHERE REPLACE(REPLACE(UPPER(uv.vehicle_number),' ',''),'-','') =
                      REPLACE(REPLACE(UPPER(?),' ',''),'-','')
                  AND uv.org_code = ?
                  AND uv.status = 'ACTIVE'
                LIMIT 1";

$residentStmt = $conn->prepare($residentSql);

if (!$residentStmt) {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to prepare resident search query"
    ]);
    exit();
}

$residentStmt->bind_param("ss", $vehicle_number, $org_code);
$residentStmt->execute();
$residentResult = $residentStmt->get_result();

if ($row = $residentResult->fetch_assoc()) {

    $parking_parts = [];

    if (!empty($row["zone_name"])) $parking_parts[] = $row["zone_name"];
    if (!empty($row["parking_slot"])) $parking_parts[] = $row["parking_slot"];
    if (!empty($row["pillar_number"])) $parking_parts[] = $row["pillar_number"];
    if (!empty($row["landmark"])) $parking_parts[] = $row["landmark"];
    if (!empty($row["floor"])) $parking_parts[] = $row["floor"];

    $parking_location = !empty($parking_parts)
        ? implode(" • ", $parking_parts)
        : "Parking details not available";

    $phone = trim($row["phone_number"] ?? "");
    $masked_contact = maskPhone($phone);

    echo json_encode([
        "status" => "found",
        "vehicle" => [
            "id" => (string)$row["id"],
            "number" => $row["vehicle_number"],
            "type" => $row["vehicle_type"] ?? "Unknown",
            "owner_name" => $row["full_name"] ?? "Unknown",
            "residence" => "My Organization",
            "parking_location" => $parking_location,
            "masked_contact" => $masked_contact,
            "real_call_number" => $phone,
            "is_approved" => true
        ]
    ]);

    $residentStmt->close();
    $conn->close();
    exit();
}

$residentStmt->close();

/*
|--------------------------------------------------------------------------
| 2) Search Visitor Vehicle
|--------------------------------------------------------------------------
*/
$visitorSql = "SELECT 
                    id,
                    vehicle_number,
                    visitor_name,
                    visitor_phone_number,
                    building,
                    floor,
                    slot_number
               FROM visitor_passes
               WHERE REPLACE(REPLACE(UPPER(vehicle_number),' ',''),'-','') =
                     REPLACE(REPLACE(UPPER(?),' ',''),'-','')
                 AND org_code = ?
                 AND status = 'ACTIVE'
               ORDER BY id DESC
               LIMIT 1";

$visitorStmt = $conn->prepare($visitorSql);

if (!$visitorStmt) {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to prepare visitor search query"
    ]);
    exit();
}

$visitorStmt->bind_param("ss", $vehicle_number, $org_code);
$visitorStmt->execute();
$visitorResult = $visitorStmt->get_result();

if ($visitorRow = $visitorResult->fetch_assoc()) {

    $parking_parts = [];

    if (!empty($visitorRow["building"])) $parking_parts[] = $visitorRow["building"];
    if (!empty($visitorRow["floor"])) $parking_parts[] = $visitorRow["floor"];
    if (!empty($visitorRow["slot_number"])) $parking_parts[] = $visitorRow["slot_number"];

    $parking_location = !empty($parking_parts)
        ? implode(" • ", $parking_parts)
        : "Parking details not available";

    $visitorPhone = trim($visitorRow["visitor_phone_number"] ?? "");
    $masked_contact = maskPhone($visitorPhone);

    echo json_encode([
        "status" => "found",
        "vehicle" => [
            "id" => "VISITOR_" . (string)$visitorRow["id"],
            "number" => $visitorRow["vehicle_number"],
            "type" => "Visitor Vehicle",
            "owner_name" => $visitorRow["visitor_name"] ?: "Visitor",
            "residence" => "Visitor Parking",
            "parking_location" => $parking_location,
            "masked_contact" => $masked_contact,
            "real_call_number" => $visitorPhone,
            "is_approved" => true
        ]
    ]);

    $visitorStmt->close();
    $conn->close();
    exit();
}

$visitorStmt->close();

echo json_encode([
    "status" => "not_found",
    "message" => "Vehicle not found in your organization"
]);

$conn->close();
?>