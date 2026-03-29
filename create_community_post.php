<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

include "db.php";

$author_type = strtoupper(trim($_POST["author_type"] ?? ""));
$title = trim($_POST["title"] ?? "");
$description = trim($_POST["description"] ?? "");

if ($author_type === "" || $title === "" || $description === "") {
    echo json_encode([
        "status" => "error",
        "message" => "Author type, title and description are required"
    ]);
    exit();
}

$org_code = "";
$author_user_id = null;
$author_admin_id = null;
$author_name = "";
$author_unit = "";
$image_path = null;

if ($author_type === "USER") {
    $user_id = isset($_POST["user_id"]) ? intval($_POST["user_id"]) : 0;

    if ($user_id <= 0) {
        echo json_encode([
            "status" => "error",
            "message" => "Valid user_id is required"
        ]);
        exit();
    }

    $stmt = $conn->prepare("SELECT full_name, org_code FROM users WHERE id = ?");
    if (!$stmt) {
        echo json_encode([
            "status" => "error",
            "message" => "Failed to prepare user query"
        ]);
        exit();
    }

    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode([
            "status" => "error",
            "message" => "User not found"
        ]);
        exit();
    }

    $row = $result->fetch_assoc();
    $author_user_id = $user_id;
    $author_name = trim($row["full_name"] ?? "");
    $org_code = trim($row["org_code"] ?? "");
    $author_unit = trim($_POST["author_unit"] ?? "");

    $stmt->close();

} elseif ($author_type === "ADMIN") {
    $admin_id = trim($_POST["admin_id"] ?? "");

    if ($admin_id === "") {
        echo json_encode([
            "status" => "error",
            "message" => "admin_id is required"
        ]);
        exit();
    }

    $stmt = $conn->prepare("
        SELECT a.full_name, o.org_code, o.org_name
        FROM admins a
        LEFT JOIN organizations o ON a.admin_id = o.admin_id
        WHERE a.admin_id = ?
        LIMIT 1
    ");

    if (!$stmt) {
        echo json_encode([
            "status" => "error",
            "message" => "Failed to prepare admin query"
        ]);
        exit();
    }

    $stmt->bind_param("s", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode([
            "status" => "error",
            "message" => "Admin not found"
        ]);
        exit();
    }

    $row = $result->fetch_assoc();
    $author_admin_id = $admin_id;
    $author_name = trim($row["full_name"] ?? "Admin");
    $org_code = trim($row["org_code"] ?? "");
    $author_unit = trim($row["org_name"] ?? "Organization Admin");

    $stmt->close();

} else {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid author_type"
    ]);
    exit();
}

if ($org_code === "") {
    echo json_encode([
        "status" => "error",
        "message" => "Organization not found for this account"
    ]);
    exit();
}

if (isset($_FILES["image"]) && $_FILES["image"]["error"] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . "/uploads/community_posts/";

    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            echo json_encode([
                "status" => "error",
                "message" => "Failed to create upload directory"
            ]);
            exit();
        }
    }

    $originalName = $_FILES["image"]["name"];
    $tmpName = $_FILES["image"]["tmp_name"];
    $fileSize = $_FILES["image"]["size"];

    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowedExtensions = ["jpg", "jpeg", "png", "webp"];

    if (!in_array($extension, $allowedExtensions)) {
        echo json_encode([
            "status" => "error",
            "message" => "Only jpg, jpeg, png and webp images are allowed"
        ]);
        exit();
    }

    if ($fileSize > 5 * 1024 * 1024) {
        echo json_encode([
            "status" => "error",
            "message" => "Image size must be less than 5 MB"
        ]);
        exit();
    }

    $newFileName = "post_" . time() . "_" . uniqid() . "." . $extension;
    $destination = $uploadDir . $newFileName;

    if (!move_uploaded_file($tmpName, $destination)) {
        echo json_encode([
            "status" => "error",
            "message" => "Failed to upload image"
        ]);
        exit();
    }

    $image_path = "uploads/community_posts/" . $newFileName;
}

$insertStmt = $conn->prepare("
    INSERT INTO community_posts (
        org_code,
        author_type,
        author_user_id,
        author_admin_id,
        author_name,
        author_unit,
        title,
        description,
        image_path
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");

if (!$insertStmt) {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to prepare insert query"
    ]);
    exit();
}

$insertStmt->bind_param(
    "ssissssss",
    $org_code,
    $author_type,
    $author_user_id,
    $author_admin_id,
    $author_name,
    $author_unit,
    $title,
    $description,
    $image_path
);

if ($insertStmt->execute()) {
    echo json_encode([
        "status" => "success",
        "message" => "Community post created successfully",
        "post_id" => $insertStmt->insert_id,
        "org_code" => $org_code,
        "image_path" => $image_path
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to create community post"
    ]);
}

$insertStmt->close();
$conn->close();
?>