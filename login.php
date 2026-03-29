<?php
require_once "db.php";

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid JSON body"
    ]);
    exit();
}

$identifier = trim($data["identifier"] ?? "");
$password   = $data["password"] ?? "";

if ($identifier === "" || $password === "") {
    echo json_encode([
        "status" => "error",
        "message" => "Email/phone and password are required"
    ]);
    exit();
}

$stmt = $conn->prepare(
    "SELECT id, full_name, email, phone_number, org_code, status, approval_seen, password
     FROM users
     WHERE email = ? OR phone_number = ?
     LIMIT 1"
);
$stmt->bind_param("ss", $identifier, $identifier);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "status" => "error",
        "message" => "User not found"
    ]);
    exit();
}

$user = $result->fetch_assoc();
$stmt->close();

if (!password_verify($password, $user["password"])) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid password"
    ]);
    exit();
}

$current_org_code = $user["org_code"];
$approval_status = strtolower(trim($user["status"] ?? "not_joined"));

/*
 Keep current active org if present.
 If user has no active org, check latest org request from user_organizations.
*/
if (empty($current_org_code) || $approval_status === "not_joined") {
    $stmt = $conn->prepare("
        SELECT org_code, status
        FROM user_organizations
        WHERE user_id = ?
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->bind_param("i", $user["id"]);
    $stmt->execute();
    $orgResult = $stmt->get_result();

    if ($orgResult->num_rows > 0) {
        $latestOrg = $orgResult->fetch_assoc();
        $current_org_code = $latestOrg["org_code"];
        $approval_status = strtolower($latestOrg["status"]);
    }

    $stmt->close();
}

echo json_encode([
    "status" => "success",
    "message" => "Login successful",
    "user" => [
        "id" => (int)$user["id"],
        "full_name" => $user["full_name"],
        "email" => $user["email"],
        "phone_number" => $user["phone_number"],
        "org_code" => $current_org_code,
        "approval_status" => $approval_status,
        "approval_seen" => (int)$user["approval_seen"]
    ]
]);

$conn->close();
?>