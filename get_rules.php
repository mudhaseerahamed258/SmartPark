<?php
header("Content-Type: application/json");
include "db.php";

if (!isset($_GET["organization_id"])) {
    echo json_encode([
        "success" => false,
        "message" => "organization_id is required"
    ]);
    exit();
}

$organization_id = intval($_GET["organization_id"]);

if ($organization_id <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid organization_id"
    ]);
    exit();
}

$sql = "SELECT 
            organization_id,
            max_vehicles_per_resident,
            visitor_parking_duration_hours,
            allow_overnight_parking,
            require_advance_booking,
            advance_booking_hours,
            max_visitors_per_day,
            visitor_pass_validity_hours,
            require_host_approval,
            require_security_verification,
            allow_guest_posting,
            post_moderation_enabled,
            allow_anonymous_posts,
            emergency_contacts_visible
        FROM organization_rules
        WHERE organization_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $organization_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode([
        "success" => true,
        "rules" => [
            "organization_id" => (int)$row["organization_id"],
            "max_vehicles_per_resident" => (int)$row["max_vehicles_per_resident"],
            "visitor_parking_duration_hours" => (int)$row["visitor_parking_duration_hours"],
            "allow_overnight_parking" => (int)$row["allow_overnight_parking"],
            "require_advance_booking" => (int)$row["require_advance_booking"],
            "advance_booking_hours" => (int)$row["advance_booking_hours"],
            "max_visitors_per_day" => (int)$row["max_visitors_per_day"],
            "visitor_pass_validity_hours" => (int)$row["visitor_pass_validity_hours"],
            "require_host_approval" => (int)$row["require_host_approval"],
            "require_security_verification" => (int)$row["require_security_verification"],
            "allow_guest_posting" => (int)$row["allow_guest_posting"],
            "post_moderation_enabled" => (int)$row["post_moderation_enabled"],
            "allow_anonymous_posts" => (int)$row["allow_anonymous_posts"],
            "emergency_contacts_visible" => (int)$row["emergency_contacts_visible"]
        ]
    ]);
} else {
    echo json_encode([
        "success" => true,
        "rules" => [
            "organization_id" => $organization_id,
            "max_vehicles_per_resident" => 3,
            "visitor_parking_duration_hours" => 4,
            "allow_overnight_parking" => 1,
            "require_advance_booking" => 0,
            "advance_booking_hours" => 0,
            "max_visitors_per_day" => 5,
            "visitor_pass_validity_hours" => 24,
            "require_host_approval" => 1,
            "require_security_verification" => 1,
            "allow_guest_posting" => 0,
            "post_moderation_enabled" => 1,
            "allow_anonymous_posts" => 0,
            "emergency_contacts_visible" => 1
        ]
    ]);
}

$stmt->close();
$conn->close();
?>