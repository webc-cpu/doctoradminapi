<?php

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
include ROOT_PATH . 'connect.php'; // ربط الاتصال
// يحتوي على الاتصال + دوال المساعدة مثل filterRequest و updateBossStatus


$boss_secretary          = filterRequest("boss_secretary");




$stmt = $con->prepare("SELECT * FROM users  WHERE `boss_user` = ? ");

$stmt->execute(array($boss_secretary));

$data =$stmt ->fetchAll(PDO::FETCH_ASSOC);

$count = $stmt->rowCount();

if ($count > 0) {
  echo json_encode(array("status" => "success", "data" => $data));
}else{
  echo json_encode(array("status" => "fail"));
}


