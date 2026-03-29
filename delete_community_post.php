<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

include "db.php";

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid input data"
    ]);
    exit();
}

$post_id = isset($data["post_id"]) ? intval($data["post_id"]) : 0;
$requester_type = strtoupper(trim($data["requester_type"] ?? ""));

if ($post_id <= 0 || $requester_type === "") {
    echo json_encode([
        "status" => "error",
        "message" => "post_id and requester_type are required"
    ]);
    exit();
}

if ($requester_type !== "USER" && $requester_type !== "ADMIN") {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid requester_type"
    ]);
    exit();
}

/*
    Get post details first
*/
$postStmt = $conn->prepare("
    SELECT
        id,
        org_code,
        author_type,
        author_user_id,
        author_admin_id,
        image_path
    FROM community_posts
    WHERE id = ?
    LIMIT 1
");

if (!$postStmt) {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to prepare post query"
    ]);
    exit();
}

$postStmt->bind_param("i", $post_id);
$postStmt->execute();
$postResult = $postStmt->get_result();

if ($postResult->num_rows === 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Post not found"
    ]);
    exit();
}

$postRow = $postResult->fetch_assoc();
$post_org_code = trim($postRow["org_code"] ?? "");
$post_author_type = strtoupper(trim($postRow["author_type"] ?? ""));
$post_author_user_id = isset($postRow["author_user_id"]) ? intval($postRow["author_user_id"]) : null;
$post_author_admin_id = $postRow["author_admin_id"] ?? null;
$image_path = trim($postRow["image_path"] ?? "");

$allowed = false;

/*
    USER can delete only own USER post
*/
if ($requester_type === "USER") {
    $user_id = isset($data["user_id"]) ? intval($data["user_id"]) : 0;

    if ($user_id <= 0) {
        echo json_encode([
            "status" => "error",
            "message" => "Valid user_id is required for USER delete"
        ]);
        exit();
    }

    $userStmt = $conn->prepare("SELECT org_code FROM users WHERE id = ? LIMIT 1");
    if (!$userStmt) {
        echo json_encode([
            "status" => "error",
            "message" => "Failed to prepare user query"
        ]);
        exit();
    }

    $userStmt->bind_param("i", $user_id);
    $userStmt->execute();
    $userResult = $userStmt->get_result();

    if ($userResult->num_rows === 0) {
        echo json_encode([
            "status" => "error",
            "message" => "User not found"
        ]);
        exit();
    }

    $userRow = $userResult->fetch_assoc();
    $user_org_code = trim($userRow["org_code"] ?? "");

    if (
        $user_org_code === $post_org_code &&
        $post_author_type === "USER" &&
        $post_author_user_id === $user_id
    ) {
        $allowed = true;
    }

    $userStmt->close();
}

/*
    ADMIN can delete:
    1. own admin post
    2. any user post in same org
*/
if ($requester_type === "ADMIN") {
    $admin_id = trim($data["admin_id"] ?? "");

    if ($admin_id === "") {
        echo json_encode([
            "status" => "error",
            "message" => "admin_id is required for ADMIN delete"
        ]);
        exit();
    }

    $adminStmt = $conn->prepare("
        SELECT o.org_code
        FROM admins a
        LEFT JOIN organizations o ON a.admin_id = o.admin_id
        WHERE a.admin_id = ?
        LIMIT 1
    ");

    if (!$adminStmt) {
        echo json_encode([
            "status" => "error",
            "message" => "Failed to prepare admin query"
        ]);
        exit();
    }

    $adminStmt->bind_param("s", $admin_id);
    $adminStmt->execute();
    $adminResult = $adminStmt->get_result();

    if ($adminResult->num_rows === 0) {
        echo json_encode([
            "status" => "error",
            "message" => "Admin not found"
        ]);
        exit();
    }

    $adminRow = $adminResult->fetch_assoc();
    $admin_org_code = trim($adminRow["org_code"] ?? "");

    if ($admin_org_code === $post_org_code) {
        if ($post_author_type === "USER") {
            $allowed = true;
        } elseif ($post_author_type === "ADMIN" && $post_author_admin_id === $admin_id) {
            $allowed = true;
        }
    }

    $adminStmt->close();
}

if (!$allowed) {
    echo json_encode([
        "status" => "error",
        "message" => "You are not allowed to delete this post"
    ]);
    exit();
}

/*
    Delete image file if it exists
*/
if ($image_path !== "") {
    $full_path = __DIR__ . "/" . $image_path;

    if (file_exists($full_path)) {
        @unlink($full_path);
    }
}

/*
    Delete post row
*/
$deleteStmt = $conn->prepare("DELETE FROM community_posts WHERE id = ?");
if (!$deleteStmt) {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to prepare delete query"
    ]);
    exit();
}

$deleteStmt->bind_param("i", $post_id);

if ($deleteStmt->execute()) {
    if ($deleteStmt->affected_rows > 0) {
        echo json_encode([
            "status" => "success",
            "message" => "Community post deleted successfully"
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Post not found or already deleted"
        ]);
    }
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to delete community post"
    ]);
}

$deleteStmt->close();
$postStmt->close();
$conn->close();
?>