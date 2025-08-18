<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

include ROOT_PATH . 'connect.php';


$user_email       = filterRequest("user_email");
$user_password       = filterRequest("user_password");

$stmt = $con->prepare("SELECT * FROM users  WHERE `user_password`=? AND user_email =?");

$stmt->execute(array( $user_password , $user_email));

$data = $stmt->fetch(PDO::FETCH_ASSOC);

$count = $stmt->rowCount();


if ($count > 0) {
echo json_encode(array("status" => "success" , "data" => $data));
}else{
echo json_encode(array("status" => "fail"));
}