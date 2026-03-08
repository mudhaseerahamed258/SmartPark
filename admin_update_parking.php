<?php
header("Content-Type: application/json");
require_once "db.php";

$data = json_decode(file_get_contents("php://input"), true);

$admin_id = trim($data["admin_id"] ?? "");
$total_slots = intval($data["total_slots"] ?? 0);
$two_wheeler_slots = intval($data["two_wheeler_slots"] ?? 0);
$four_wheeler_slots = intval($data["four_wheeler_slots"] ?? 0);
$visitor_slots = intval($data["visitor_slots"] ?? 0);
$disabled_slots = intval($data["disabled_slots"] ?? 0);
$ev_slots = intval($data["ev_slots"] ?? 0);
$parking_hours = trim($data["parking_hours"] ?? "");
$parking_rules = trim($data["parking_rules"] ?? "");

if ($admin_id === "") {
    echo json_encode([
        "status" => "error",
        "message" => "Admin ID required"
    ]);
    exit();
}

/* Optional: verify admin exists */
$stmt = $conn->prepare("SELECT admin_id FROM admins WHERE admin_id = ? LIMIT 1");
$stmt->bind_param("s", $admin_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Admin not found"
    ]);
    exit();
}
$stmt->close();

/* Find organization directly by admin_id string */
$stmt = $conn->prepare("SELECT id FROM organizations WHERE admin_id = ? LIMIT 1");
$stmt->bind_param("s", $admin_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Organization not found"
    ]);
    exit();
}

$org = $res->fetch_assoc();
$organization_id = (int)$org["id"];
$stmt->close();

/* Check if parking exists */
$stmt = $conn->prepare("SELECT id FROM organization_parking WHERE organization_id = ? LIMIT 1");
$stmt->bind_param("i", $organization_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    $parking = $res->fetch_assoc();
    $parking_id = (int)$parking["id"];
    $stmt->close();

    $stmt = $conn->prepare("
        UPDATE organization_parking
        SET total_slots = ?,
            two_wheeler_slots = ?,
            four_wheeler_slots = ?,
            visitor_slots = ?,
            disabled_slots = ?,
            ev_slots = ?,
            parking_hours = ?,
            parking_rules = ?
        WHERE id = ?
    ");

    $stmt->bind_param(
        "iiiiiissi",
        $total_slots,
        $two_wheeler_slots,
        $four_wheeler_slots,
        $visitor_slots,
        $disabled_slots,
        $ev_slots,
        $parking_hours,
        $parking_rules,
        $parking_id
    );

    $success = $stmt->execute();
} else {
    $stmt->close();

    $stmt = $conn->prepare("
        INSERT INTO organization_parking
        (organization_id, total_slots, two_wheeler_slots, four_wheeler_slots, visitor_slots, disabled_slots, ev_slots, parking_hours, parking_rules)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "iiiiiiiss",
        $organization_id,
        $total_slots,
        $two_wheeler_slots,
        $four_wheeler_slots,
        $visitor_slots,
        $disabled_slots,
        $ev_slots,
        $parking_hours,
        $parking_rules
    );

    $success = $stmt->execute();
}

if ($success) {
    echo json_encode([
        "status" => "success",
        "message" => "Parking updated successfully"
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to update parking"
    ]);
}

$stmt->close();
$conn->close();
?>