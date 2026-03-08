<?php
require_once "db.php";

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);
$org_code = trim($data["org_code"] ?? "");

if ($org_code == "") {
    echo json_encode([
        "status" => "error",
        "message" => "org_code is required"
    ]);
    exit();
}

$stmt = $conn->prepare("
    SELECT 
        o.org_name,
        o.org_code,
        o.address,
        o.city,
        o.state,
        o.pincode,
        o.contact_phone,
        o.contact_email,
        o.description,
        p.total_slots,
        p.two_wheeler_slots,
        p.four_wheeler_slots,
        p.visitor_slots,
        p.parking_hours
    FROM organizations o
    LEFT JOIN organization_parking p 
        ON o.id = p.organization_id
    WHERE o.org_code = ?
    LIMIT 1
");

if (!$stmt) {
    echo json_encode([
        "status" => "error",
        "message" => "Prepare failed: " . $conn->error
    ]);
    exit();
}

$stmt->bind_param("s", $org_code);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows == 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Organization not found"
    ]);
    exit();
}

$org = $result->fetch_assoc();

echo json_encode([
    "status" => "success",
    "message" => "Organization found",
    "organization" => [
        "org_name" => $org["org_name"] ?? "",
        "org_code" => $org["org_code"] ?? "",
        "address" => $org["address"] ?? "",
        "city" => $org["city"] ?? "",
        "state" => $org["state"] ?? "",
        "pincode" => $org["pincode"] ?? "",
        "contact_phone" => $org["contact_phone"] ?? "",
        "contact_email" => $org["contact_email"] ?? "",
        "description" => $org["description"] ?? "",
        "total_slots" => (int)($org["total_slots"] ?? 0),
        "two_wheeler_slots" => (int)($org["two_wheeler_slots"] ?? 0),
        "four_wheeler_slots" => (int)($org["four_wheeler_slots"] ?? 0),
        "visitor_slots" => (int)($org["visitor_slots"] ?? 0),
        "parking_hours" => $org["parking_hours"] ?? ""
    ]
]);

$stmt->close();
$conn->close();
?>