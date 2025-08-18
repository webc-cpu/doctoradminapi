<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

include ROOT_PATH . 'connect.php';

$setting_id = filterRequest("setting_id");

$stmt = $con->prepare("DELETE FROM treatment_settings WHERE setting_id = ?");
$isDeleted = $stmt->execute(array($setting_id));

if ($isDeleted) {
    echo json_encode(array("status" => "success"));
} else {
    echo json_encode(array("status" => "fail"));
}
?>