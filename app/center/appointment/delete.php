<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
include ROOT_PATH . 'connect.php'; // ربط الاتصال

// استلام ID الموعد المراد حذفه
$secretary_id = filterRequest("appointment_id");

// تنفيذ أمر الحذف
$stmt = $con->prepare("DELETE FROM `appointments` WHERE `appointment_id` = ?");
$stmt->execute([$secretary_id]);

$count = $stmt->rowCount();

if ($count > 0) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "fail", "message" => "ما تم العثور على الموعد أو الحذف فشل"]);
}
