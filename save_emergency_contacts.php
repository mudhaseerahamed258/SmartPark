<?php
header("Content-Type: application/json");
include "db.php";

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON data"
    ]);
    exit();
}

$organization_id = isset($data["organization_id"]) ? intval($data["organization_id"]) : 0;
$security_office = trim($data["security_office"] ?? "");
$management_office = trim($data["management_office"] ?? "");
$gate_security = trim($data["gate_security"] ?? "");

if ($organization_id <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid organization_id"
    ]);
    exit();
}

if ($security_office === "" || $management_office === "" || $gate_security === "") {
    echo json_encode([
        "success" => false,
        "message" => "All contact numbers are required"
    ]);
    exit();
}

$checkSql = "SELECT id FROM organization_emergency_contacts WHERE organization_id = ?";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->bind_param("i", $organization_id);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    $sql = "UPDATE organization_emergency_contacts
            SET security_office = ?, management_office = ?, gate_security = ?
            WHERE organization_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "sssi",
        $security_office,
        $management_office,
        $gate_security,
        $organization_id
    );
} else {
    $sql = "INSERT INTO organization_emergency_contacts
            (organization_id, security_office, management_office, gate_security)
            VALUES (?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "isss",
        $organization_id,
        $security_office,
        $management_office,
        $gate_security
    );
}

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Emergency contacts saved successfully"
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Database error",
        "error" => $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>