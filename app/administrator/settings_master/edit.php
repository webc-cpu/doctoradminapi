<?php
header("Content-Type: application/json");
include ROOT_PATH . 'connect.php';

$setting_key    = filterRequest("setting_key");
$label          = filterRequest("label");
$default_value  = filterRequest("default_value");

// التحقق من الحقول المطلوبة
if (!$setting_key || !$label || $default_value === null) {
    echo json_encode([
        "status" => "fail",
        "message" => "Missing required fields: setting_key, label, default_value"
    ]);
    exit;
}

try {
    $stmt = $con->prepare("
        UPDATE settings_master
        SET label = ?, default_value = ?
        WHERE setting_key = ?
    ");
    $stmt->execute([$label, $default_value, $setting_key]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            "status" => "success",
            "message" => "تم تحديث الإعداد بنجاح"
        ]);
    } else {
        echo json_encode([
            "status" => "fail",
            "message" => "الإعداد غير موجود أو لم يحدث أي تغيير"
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        "status" => "fail",
        "message" => $e->getMessage()
    ]);
}
