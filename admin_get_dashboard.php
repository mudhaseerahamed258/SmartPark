<?php
require_once "db.php";

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

$admin_id = trim($data["admin_id"] ?? "");

if ($admin_id === "") {
    echo json_encode([
        "status" => "error",
        "message" => "Admin id missing"
    ]);
    exit();
}

/* Get organization directly by admin_id string */
$stmt = $conn->prepare("
    SELECT id, org_name, org_code, address, city, state, pincode,
           contact_phone, contact_email, description
    FROM organizations
    WHERE admin_id = ?
    LIMIT 1
");

$stmt->bind_param("s", $admin_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Organization not found"
    ]);
    exit();
}

$org = $result->fetch_assoc();
$organization_id = (int)$org["id"];

/* Get parking using organization_id */
$stmt = $conn->prepare("
    SELECT total_slots, two_wheeler_slots, four_wheeler_slots,
           visitor_slots, disabled_slots, ev_slots, parking_hours, parking_rules
    FROM organization_parking
    WHERE organization_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $organization_id);
$stmt->execute();
$parkingResult = $stmt->get_result();

$parking = [
    "total_slots" => 0,
    "two_wheeler_slots" => 0,
    "four_wheeler_slots" => 0,
    "visitor_slots" => 0,
    "disabled_slots" => 0,
    "ev_slots" => 0,
    "parking_hours" => "",
    "parking_rules" => ""
];

if ($parkingResult->num_rows > 0) {
    $parking = $parkingResult->fetch_assoc();
}

/* Remove internal id before sending */
unset($org["id"]);

echo json_encode([
    "status" => "success",
    "organization" => $org,
    "parking" => $parking
]);

$conn->close();
?>