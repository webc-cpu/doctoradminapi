<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
include ROOT_PATH . 'connect.php';

$id                     = filterRequest("id");
$version_code           = filterRequest("version_code");
$update_links             = filterRequest("update_links");
$name                   = filterRequest("name");


$stmt = $con->prepare("UPDATE `app_version` SET
`name`= ? ,`version_code`= ? ,`update_links`= ?  WHERE id =?
");

$stmt->execute(array( $name ,$version_code ,$update_links ,$id, ));

$count = $stmt->rowCount();

if ($count > 0) {
  echo json_encode(array("status" => "success"));
}