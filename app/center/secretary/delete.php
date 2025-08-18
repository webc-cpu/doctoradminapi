<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
include ROOT_PATH . 'connect.php'; // ربط الاتصال
// يحتوي على الاتصال + دوال المساعدة مثل filterRequest و updateBossStatus

// استلام ID السكرتير المراد حذفه
$secretary_id = filterRequest("secretary_id");

// تنفيذ أمر الحذف
$stmt = $con->prepare("DELETE FROM `secretaries` WHERE `secretary_id` = ?");
$stmt->execute([$secretary_id]);

$count = $stmt->rowCount();

if ($count > 0) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "fail", "message" => "ما تم العثور على السكرتير أو الحذف فشل"]);
}
