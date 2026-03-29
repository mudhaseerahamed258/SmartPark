<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

include "db.php";

$org_code = trim($_GET["org_code"] ?? "");

if ($org_code === "") {
    echo json_encode([
        "status" => "error",
        "message" => "org_code is required"
    ]);
    exit();
}

$stmt = $conn->prepare("
    SELECT
        id,
        org_code,
        author_type,
        author_user_id,
        author_admin_id,
        author_name,
        author_unit,
        title,
        description,
        image_path,
        created_at
    FROM community_posts
    WHERE org_code = ?
    ORDER BY id DESC
");

if (!$stmt) {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to prepare query"
    ]);
    exit();
}

$stmt->bind_param("s", $org_code);
$stmt->execute();
$result = $stmt->get_result();

$posts = [];

while ($row = $result->fetch_assoc()) {
    $posts[] = [
        "id" => strval($row["id"]),
        "authorName" => $row["author_name"],
        "authorUnit" => $row["author_unit"],
        "authorRole" => strtoupper($row["author_type"]),
        "title" => $row["title"],
        "description" => $row["description"],
        "timestamp" => $row["created_at"],
        "orgCode" => $row["org_code"],
        "authorUserId" => $row["author_user_id"] !== null ? intval($row["author_user_id"]) : null,
        "authorAdminId" => $row["author_admin_id"],
        "imagePath" => $row["image_path"]
    ];
}

echo json_encode([
    "status" => "success",
    "posts" => $posts
]);

$stmt->close();
$conn->close();
?>