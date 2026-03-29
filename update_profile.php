<?php
header("Content-Type: application/json");
include "db.php";

$data = json_decode(file_get_contents("php://input"), true);

$user_id = $data["user_id"] ?? 0;
$name = $data["name"] ?? "";
$flat = $data["flat"] ?? "";
$email = $data["email"] ?? "";
$phone = $data["phone"] ?? "";

if ($user_id == 0) {
    echo json_encode(["status"=>"error","message"=>"Invalid user"]);
    exit;
}

$sql = "UPDATE users 
        SET full_name=?, flat=?, email=?, phone_number=? 
        WHERE id=?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssi",$name,$flat,$email,$phone,$user_id);

if($stmt->execute()){
    echo json_encode(["status"=>"success","message"=>"Profile updated"]);
}else{
    echo json_encode(["status"=>"error","message"=>"Update failed"]);
}
?>