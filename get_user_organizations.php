<?php
require_once "db.php";

header("Content-Type: application/json");

$user_id = isset($_GET["user_id"]) ? intval($_GET["user_id"]) : 0;

if ($user_id <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Valid user_id is required"
    ]);
    exit();
}

$sql = "
    SELECT 
        uo.org_code,
        uo.status,
        o.org_name
    FROM user_organizations uo
    INNER JOIN organizations o ON o.org_code = uo.org_code
    WHERE uo.user_id = ?
    ORDER BY 
        CASE 
            WHEN uo.status = 'APPROVED' THEN 1
            WHEN uo.status = 'PENDING' THEN 2
            WHEN uo.status = 'REJECTED' THEN 3
            ELSE 4
        END,
        o.org_name ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$organizations = [];

while ($row = $result->fetch_assoc()) {
    $organizations[] = [
        "org_code" => $row["org_code"],
        "org_name" => $row["org_name"],
        "status"   => $row["status"]
    ];
}

echo json_encode([
    "status" => "success",
    "organizations" => $organizations
]);

$stmt->close();
$conn->close();
?>