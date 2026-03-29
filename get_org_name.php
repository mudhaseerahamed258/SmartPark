<?php
header("Content-Type: application/json");
include "db.php";

$org_code = $_GET['org_code'] ?? '';

if ($org_code == "") {
    echo json_encode(["status"=>"error","message"=>"Org code required"]);
    exit;
}

$stmt = $conn->prepare("SELECT org_name FROM organizations WHERE org_code=?");
$stmt->bind_param("s",$org_code);
$stmt->execute();
$result = $stmt->get_result();

if($row = $result->fetch_assoc()){
    echo json_encode([
        "status"=>"success",
        "org_name"=>$row["org_name"]
    ]);
}else{
    echo json_encode([
        "status"=>"error",
        "message"=>"Organization not found"
    ]);
}
?>