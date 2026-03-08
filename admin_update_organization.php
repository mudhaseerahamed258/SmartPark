<?php
header("Content-Type: application/json");
require_once "db.php";

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid request"
    ]);
    exit();
}

$admin_id = trim($data["admin_id"] ?? "");
$org_name = trim($data["org_name"] ?? "");
$org_code = trim($data["org_code"] ?? "");
$address = trim($data["address"] ?? "");
$city = trim($data["city"] ?? "");
$state = trim($data["state"] ?? "");
$pincode = trim($data["pincode"] ?? "");
$contact_phone = trim($data["contact_phone"] ?? "");
$contact_email = trim($data["contact_email"] ?? "");
$description = trim($data["description"] ?? "");

if ($admin_id === "" || $org_name === "" || $org_code === "") {
    echo json_encode([
        "status" => "error",
        "message" => "Required fields missing"
    ]);
    exit();
}

/* Verify admin exists */
$getAdmin = $conn->prepare("SELECT admin_id FROM admins WHERE admin_id = ? LIMIT 1");
$getAdmin->bind_param("s", $admin_id);
$getAdmin->execute();
$adminResult = $getAdmin->get_result();

if ($adminResult->num_rows === 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Admin not found"
    ]);
    exit();
}
$getAdmin->close();

/* Check if organization already exists for this admin */
$checkOrg = $conn->prepare("SELECT id FROM organizations WHERE admin_id = ? LIMIT 1");
$checkOrg->bind_param("s", $admin_id);
$checkOrg->execute();
$orgResult = $checkOrg->get_result();

$orgSuccess = false;

if ($orgResult->num_rows > 0) {
    $orgRow = $orgResult->fetch_assoc();
    $org_db_id = (int)$orgRow["id"];

    $updateOrg = $conn->prepare("
        UPDATE organizations
        SET org_name = ?, org_code = ?, address = ?, city = ?, state = ?, pincode = ?, contact_phone = ?, contact_email = ?, description = ?, updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");

    $updateOrg->bind_param(
        "sssssssssi",
        $org_name,
        $org_code,
        $address,
        $city,
        $state,
        $pincode,
        $contact_phone,
        $contact_email,
        $description,
        $org_db_id
    );

    $orgSuccess = $updateOrg->execute();
    $updateOrg->close();
} else {
    $insertOrg = $conn->prepare("
        INSERT INTO organizations
        (admin_id, org_name, org_code, address, city, state, pincode, contact_phone, contact_email, description)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $insertOrg->bind_param(
        "ssssssssss",
        $admin_id,
        $org_name,
        $org_code,
        $address,
        $city,
        $state,
        $pincode,
        $contact_phone,
        $contact_email,
        $description
    );

    $orgSuccess = $insertOrg->execute();
    $insertOrg->close();
}

if (!$orgSuccess) {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to save organization"
    ]);
    exit();
}

/* Sync org_name into admins table too */
$updateAdmin = $conn->prepare("
    UPDATE admins
    SET org_name = ?
    WHERE admin_id = ?
");
$updateAdmin->bind_param("ss", $org_name, $admin_id);
$adminSuccess = $updateAdmin->execute();
$updateAdmin->close();

if ($adminSuccess) {
    echo json_encode([
        "status" => "success",
        "message" => "Organization updated successfully"
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Organization updated, but failed to sync admin org name"
    ]);
}

$conn->close();
?>