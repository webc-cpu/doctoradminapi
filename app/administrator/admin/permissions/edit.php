<?php
include ROOT_PATH . 'connect.php'; // ربط الاتصال

$id_boss       = filterRequest("id_boss");
$setting_key   = filterRequest("setting_key");
$setting_value = filterRequest("setting_value");

$stmt = $con->prepare("UPDATE boss_settings SET setting_value = ? WHERE id_boss = ? AND setting_key = ?");
$stmt->execute([$setting_value, $id_boss, $setting_key]);

if ($stmt->rowCount() > 0) {
    echo json_encode([
        "status" => "success",
        "message" => "تم التعديل بنجاح"
    ]);
} else {
    echo json_encode([
        "status" => "failed",
        "message" => "لم يتم تعديل أي بيانات (تأكد من صحة البيانات أو أنه لا يوجد تغيير)"
    ]);
}
