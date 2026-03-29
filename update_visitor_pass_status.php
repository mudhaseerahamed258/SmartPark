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

$pass_id = isset($data["pass_id"]) ? intval($data["pass_id"]) : 0;
$status = strtoupper(trim($data["status"] ?? ""));

if ($pass_id <= 0 || $status === "") {
    echo json_encode([
        "status" => "error",
        "message" => "pass_id and status are required"
    ]);
    exit();
}

$allowedStatuses = ["ACTIVE", "PAST"];
if (!in_array($status, $allowedStatuses)) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid status"
    ]);
    exit();
}

$stmt = $conn->prepare("UPDATE visitor_passes SET status = ? WHERE id = ?");
if (!$stmt) {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to prepare update query"
    ]);
    exit();
}

$stmt->bind_param("si", $status, $pass_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode([
            "status" => "success",
            "message" => "Visitor pass status updated successfully"
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Visitor pass not found or already updated"
        ]);
    }
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to update visitor pass status"
    ]);
}

$stmt->close();
$conn->close();
?>