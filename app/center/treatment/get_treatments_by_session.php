<?php

// <!-- عرض العلاجات الخاصين بالجلسة -->


header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

include ROOT_PATH . 'connect.php';

$patient_id = filterRequest("session_id");

$stmt = $con->prepare("SELECT * FROM treatment WHERE `session_id` = ?");
$stmt->execute(array($patient_id));

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
$count = $stmt->rowCount();

if ($count > 0) {
    echo json_encode(array("status" => "success", "data" => $data));
} else {
    echo json_encode(array("status" => "fail"));
}
?>


