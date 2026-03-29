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
            r.max_vehicles_per_resident,
            r.visitor_parking_duration_hours,
            r.allow_overnight_parking,
            r.require_advance_booking,
            r.advance_booking_hours,
            r.max_visitors_per_day,
            r.visitor_pass_validity_hours,
            r.require_host_approval,
            r.require_security_verification,
            r.allow_guest_posting,
            r.post_moderation_enabled,
            r.allow_anonymous_posts,
            r.emergency_contacts_visible
        FROM organizations o
        LEFT JOIN organization_rules r ON o.id = r.organization_id
        WHERE o.org_code = ?
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $org_code);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode([
        "success" => true,
        "rules" => [
            "organization_id" => (int)$row["organization_id"],
            "max_vehicles_per_resident" => isset($row["max_vehicles_per_resident"]) ? (int)$row["max_vehicles_per_resident"] : 3,
            "visitor_parking_duration_hours" => isset($row["visitor_parking_duration_hours"]) ? (int)$row["visitor_parking_duration_hours"] : 4,
            "allow_overnight_parking" => isset($row["allow_overnight_parking"]) ? (int)$row["allow_overnight_parking"] : 1,
            "require_advance_booking" => isset($row["require_advance_booking"]) ? (int)$row["require_advance_booking"] : 0,
            "advance_booking_hours" => isset($row["advance_booking_hours"]) ? (int)$row["advance_booking_hours"] : 0,
            "max_visitors_per_day" => isset($row["max_visitors_per_day"]) ? (int)$row["max_visitors_per_day"] : 5,
            "visitor_pass_validity_hours" => isset($row["visitor_pass_validity_hours"]) ? (int)$row["visitor_pass_validity_hours"] : 24,
            "require_host_approval" => isset($row["require_host_approval"]) ? (int)$row["require_host_approval"] : 1,
            "require_security_verification" => isset($row["require_security_verification"]) ? (int)$row["require_security_verification"] : 1,
            "allow_guest_posting" => isset($row["allow_guest_posting"]) ? (int)$row["allow_guest_posting"] : 0,
            "post_moderation_enabled" => isset($row["post_moderation_enabled"]) ? (int)$row["post_moderation_enabled"] : 1,
            "allow_anonymous_posts" => isset($row["allow_anonymous_posts"]) ? (int)$row["allow_anonymous_posts"] : 0,
            "emergency_contacts_visible" => isset($row["emergency_contacts_visible"]) ? (int)$row["emergency_contacts_visible"] : 1
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