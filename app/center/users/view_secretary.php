<?php
// مالو شغل ابدا حاليا لانو مربوط بالرولز 
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
include ROOT_PATH . 'connect.php';

$id_boss = filterRequest("id_boss");

$stmt = $con->prepare("SELECT * FROM users WHERE `boss_user` = ? AND `user_role` = 'سكرتيرة'");
$stmt->execute([$id_boss]);

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
$count = $stmt->rowCount();

if ($count > 0) {
    echo json_encode(["status" => "success", "data" => $data]);
} else {
    echo json_encode(["status" => "fail"]);
}
