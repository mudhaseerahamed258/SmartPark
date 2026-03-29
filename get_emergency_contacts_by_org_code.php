<?php
header("Content-Type: application/json");
include "db.php";

if (!isset($_GET["org_code"])) {
    echo json_encode([
        "success" => false,
        "message" => "org_code is required"
    ]);
    exit();
}

$org_code = trim($_GET["org_code"]);

if ($org_code === "") {
    echo json_encode([
        "success" => false,
        "message" => "Invalid org_code"
    ]);
    exit();
}

$sql = "SELECT
            o.id AS organization_id,
            c.security_office,
            c.management_office,
            c.gate_security
        FROM organizations o
        LEFT JOIN organization_emergency_contacts c
            ON o.id = c.organization_id
        WHERE o.org_code = ?
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $org_code);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode([
        "success" => true,
        "contacts" => [
            "organization_id" => (int)$row["organization_id"],
            "security_office" => $row["security_office"] ?? "9876543210",
            "management_office" => $row["management_office"] ?? "9876500001",
            "gate_security" => $row["gate_security"] ?? "9876511111"
        ]
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Organization not found"
    ]);
}

$stmt->close();
$conn->close();
?>