<?php
include ROOT_PATH . 'connect.php'; // ربط الاتصال
// يحتوي على الاتصال + دوال المساعدة مثل filterRequest و updateBossStatus

$user_id = filterRequest("user_id");

// تحقق من وجود user_id
if (empty($user_id)) {
    echo json_encode(array("status" => "fail", "message" => "user_id is required"));
    exit;
}

$stmt = $con->prepare("SELECT * FROM patients WHERE `user_patient` = ? ");
$stmt->execute(array($user_id));

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
$count = $stmt->rowCount();

if ($count > 0) {
    echo json_encode(array("status" => "success", "data" => $data));
} else {
    echo json_encode(array("status" => "fail", "message" => "No patient data found"));
}