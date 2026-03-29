<?php
require_once "db.php";

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

$user_id = intval($data["user_id"] ?? 0);
$org_code = trim($data["org_code"] ?? "");

if ($user_id <= 0 || $org_code === "") {
    echo json_encode([
        "status" => "error",
        "message" => "user_id and org_code are required"
    ]);
    exit();
}

/* Check if organization exists */
$stmt = $conn->prepare("SELECT org_name FROM organizations WHERE org_code = ?");
$stmt->bind_param("s", $org_code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid organization code"
    ]);
    exit();
}

$stmt->close();

/* Check if request already exists */
$stmt = $conn->prepare("
    SELECT id, status
    FROM user_organizations
    WHERE user_id = ? AND org_code = ?
    LIMIT 1
");
$stmt->bind_param("is", $user_id, $org_code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {

    $row = $result->fetch_assoc();
    $existingStatus = strtoupper($row["status"]);

    if ($existingStatus === "PENDING") {
        echo json_encode([
            "status" => "error",
            "message" => "You already requested to join this organization. Waiting for admin approval."
        ]);
        exit();
    }

    if ($existingStatus === "APPROVED") {
        echo json_encode([
            "status" => "error",
            "message" => "You are already a member of this organization."
        ]);
        exit();
    }

    if ($existingStatus === "REJECTED") {
        echo json_encode([
            "status" => "error",
            "message" => "Your request was rejected previously. Contact admin."
        ]);
        exit();
    }
}

$stmt->close();

/* Insert new join request */
$stmt = $conn->prepare("
    INSERT INTO user_organizations (user_id, org_code, status)
    VALUES (?, ?, 'PENDING')
");
$stmt->bind_param("is", $user_id, $org_code);

if ($stmt->execute()) {

    /* If user never joined any org before */
    $update = $conn->prepare("
        UPDATE users
        SET org_code = ?, status = 'pending'
        WHERE id = ? AND (org_code IS NULL OR org_code = '')
    ");
    $update->bind_param("si", $org_code, $user_id);
    $update->execute();
    $update->close();

    echo json_encode([
        "status" => "success",
        "message" => "Join request submitted successfully"
    ]);

} else {

    echo json_encode([
        "status" => "error",
        "message" => "Failed to submit join request"
    ]);

}

$stmt->close();
$conn->close();
?>