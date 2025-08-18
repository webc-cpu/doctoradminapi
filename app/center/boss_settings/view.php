<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

include ROOT_PATH . 'connect.php';

$id_boss = filterRequest("id_boss");

$stmt = $con->prepare("SELECT * FROM boss_settings WHERE id_boss = ?");
$stmt->execute(array($id_boss));

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
$count = $stmt->rowCount();

echo json_encode(array("status" => "success", "data" => $data));
?>