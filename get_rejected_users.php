<?php
require_once "db.php";

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

$admin_id = trim($data["admin_id"] ?? "");

if ($admin_id === "") {
    echo json_encode([
        "status" => "error",
        "message" => "admin_id required"
    ]);
    exit();
}

/* Get org_code for this admin */
$stmt = $conn->prepare("SELECT org_code FROM organizations WHERE admin_id = ? LIMIT 1");
$stmt->bind_param("s", $admin_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "status" => "error",
        "message" => "organization not found"
    ]);
    exit();
}

$row = $result->fetch_assoc();
$org_code = $row["org_code"];
$stmt->close();

/* Get rejected users for this organization from user_organizations */
$stmt = $conn->prepare("
    SELECT 
        u.id,
        u.full_name,
        u.email,
        u.phone_number,
        'rejected' AS status
    FROM user_organizations uo
    INNER JOIN users u ON u.id = uo.user_id
    WHERE uo.org_code = ?
      AND uo.status = 'REJECTED'
    ORDER BY uo.id DESC
");
$stmt->bind_param("s", $org_code);
$stmt->execute();
$result = $stmt->get_result();

$users = [];

while ($row = $result->fetch_assoc()) {
    $users[] = [
        "id" => strval($row["id"]),
        "full_name" => $row["full_name"],
        "email" => $row["email"],
        "phone_number" => $row["phone_number"],
        "status" => $row["status"]
    ];
}

echo json_encode([
    "status" => "success",
    "users" => $users
]);

$stmt->close();
$conn->close();
?>