<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

include ROOT_PATH . 'connect.php';


$secretary_email       = filterRequest("secretary_email");
$secretary_password       = filterRequest("secretary_password");

$stmt = $con->prepare("SELECT * FROM secretaries  WHERE `secretary_password`=? AND secretary_email =?");

$stmt->execute(array( $secretary_password , $secretary_email));

$data = $stmt->fetch(PDO::FETCH_ASSOC);

$count = $stmt->rowCount();


if ($count > 0) {
echo json_encode(array("status" => "success" , "data" => $data));
}else{
echo json_encode(array("status" => "fail"));
}