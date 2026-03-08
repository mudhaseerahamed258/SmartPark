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
    SELECT org_name, org_code, address, city, state, pincode, contact_phone, contact_email, description
    FROM organizations
    WHERE org_code = ?
    LIMIT 1
");
$stmt->bind_param("s", $org_code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
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
        "org_name" => $org["org_name"],
        "org_code" => $org["org_code"],
        "address" => $org["address"],
        "city" => $org["city"],
        "state" => $org["state"],
        "pincode" => $org["pincode"],
        "contact_phone" => $org["contact_phone"],
        "contact_email" => $org["contact_email"],
        "description" => $org["description"]
    ]
]);

$stmt->close();
$conn->close();
?>