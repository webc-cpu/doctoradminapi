<?php

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
include ROOT_PATH . 'connect.php'; // ربط الاتصال
// يحتوي على الاتصال + دوال المساعدة مثل filterRequest و updateBossStatus

$secretary_id = filterRequest("secretary_id");

$stmt = $con->prepare("SELECT * FROM secretaries WHERE secretary_id = :secretary_id");
$stmt->bindParam(':secretary_id', $secretary_id, PDO::PARAM_INT); // ربط المعامل بالقيمة
$stmt->execute();

$data = $stmt->fetchAll(PDO::FETCH_ASSOC); // جلب البيانات

$count = $stmt->rowCount();

if ($count > 0) {
    echo json_encode(array("status" => "success", "data" => $data));
} else {
    echo json_encode(array("status" => "fail"));
}
