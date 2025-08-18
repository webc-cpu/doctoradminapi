<?php

// header("Access-Control-Allow-Origin: *");
// header("Access-Control-Allow-Headers: Content-Type, Authorization");
// header("Access-Control-Allow-Methods: POST, OPTIONS");

// include ROOT_PATH . 'connect.php';


// $Administrator_email       = filterRequest("Administrator_email");
// $Administrator_Password       = filterRequest("Administrator_Password");

// $stmt = $con->prepare("SELECT * FROM administrator  WHERE `Administrator_Password`=? AND Administrator_email =?");

// $stmt->execute(array( $Administrator_Password , $Administrator_email));

// $data = $stmt->fetch(PDO::FETCH_ASSOC);

// $count = $stmt->rowCount();


// if ($count > 0) {
// echo json_encode(array("status" => "success" , "data" => $data));
// }else{
// echo json_encode(array("status" => "fail"));
// }