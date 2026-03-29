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

if ($organization_id <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid organization_id"
    ]);
    exit();
}

/* Parking Rules */
$max_vehicles_per_resident = isset($data["max_vehicles_per_resident"]) ? intval($data["max_vehicles_per_resident"]) : 3;
$visitor_parking_duration_hours = isset($data["visitor_parking_duration_hours"]) ? intval($data["visitor_parking_duration_hours"]) : 4;
$allow_overnight_parking = isset($data["allow_overnight_parking"]) && $data["allow_overnight_parking"] ? 1 : 0;
$require_advance_booking = isset($data["require_advance_booking"]) && $data["require_advance_booking"] ? 1 : 0;
$advance_booking_hours = isset($data["advance_booking_hours"]) ? intval($data["advance_booking_hours"]) : 0;

/* Visitor Rules */
$max_visitors_per_day = isset($data["max_visitors_per_day"]) ? intval($data["max_visitors_per_day"]) : 5;
$visitor_pass_validity_hours = isset($data["visitor_pass_validity_hours"]) ? intval($data["visitor_pass_validity_hours"]) : 24;
$require_host_approval = isset($data["require_host_approval"]) && $data["require_host_approval"] ? 1 : 0;
$require_security_verification = isset($data["require_security_verification"]) && $data["require_security_verification"] ? 1 : 0;

/* Community Rules */
$allow_guest_posting = isset($data["allow_guest_posting"]) && $data["allow_guest_posting"] ? 1 : 0;
$post_moderation_enabled = isset($data["post_moderation_enabled"]) && $data["post_moderation_enabled"] ? 1 : 0;
$allow_anonymous_posts = isset($data["allow_anonymous_posts"]) && $data["allow_anonymous_posts"] ? 1 : 0;
$emergency_contacts_visible = isset($data["emergency_contacts_visible"]) && $data["emergency_contacts_visible"] ? 1 : 0;

/* Check if rules already exist */
$checkSql = "SELECT id FROM organization_rules WHERE organization_id = ?";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->bind_param("i", $organization_id);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {

    $sql = "UPDATE organization_rules SET
        max_vehicles_per_resident=?,
        visitor_parking_duration_hours=?,
        allow_overnight_parking=?,
        require_advance_booking=?,
        advance_booking_hours=?,
        max_visitors_per_day=?,
        visitor_pass_validity_hours=?,
        require_host_approval=?,
        require_security_verification=?,
        allow_guest_posting=?,
        post_moderation_enabled=?,
        allow_anonymous_posts=?,
        emergency_contacts_visible=?
        WHERE organization_id=?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "iiiiiiiiiiiiii",
        $max_vehicles_per_resident,
        $visitor_parking_duration_hours,
        $allow_overnight_parking,
        $require_advance_booking,
        $advance_booking_hours,
        $max_visitors_per_day,
        $visitor_pass_validity_hours,
        $require_host_approval,
        $require_security_verification,
        $allow_guest_posting,
        $post_moderation_enabled,
        $allow_anonymous_posts,
        $emergency_contacts_visible,
        $organization_id
    );

} else {

    $sql = "INSERT INTO organization_rules (
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
    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "iiiiiiiiiiiiii",
        $organization_id,
        $max_vehicles_per_resident,
        $visitor_parking_duration_hours,
        $allow_overnight_parking,
        $require_advance_booking,
        $advance_booking_hours,
        $max_visitors_per_day,
        $visitor_pass_validity_hours,
        $require_host_approval,
        $require_security_verification,
        $allow_guest_posting,
        $post_moderation_enabled,
        $allow_anonymous_posts,
        $emergency_contacts_visible
    );
}

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Rules saved successfully"
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