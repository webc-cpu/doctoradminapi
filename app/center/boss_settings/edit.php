<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");

include ROOT_PATH . 'connect.php'; // اتصال بقاعدة البيانات

// استقبال البيانات مع تنظيفها من الفراغات الزائدة
$id_boss       = trim(filterRequest("id_boss"));       
$setting_key   = trim(filterRequest("setting_key"));   
$setting_value = filterRequest("setting_value"); // لا نعمل trim هنا لأنه ممكن يكون "0" أو قيمة تحتوي مسافات مقصودة

// التحقق من وجود الحقول المطلوبة (مع قبول "0" كقيمة صحيحة للـ setting_value)
if ($id_boss === '' || $setting_key === '' || $setting_value === null) {
    echo json_encode([
        "status" => "fail",
        "message" => "Missing required fields: id_boss, setting_key, setting_value"
    ]);
    exit;
}

try {
    // تحديث قيمة setting_value حسب id_boss و setting_key
    $stmt = $con->prepare("
        UPDATE boss_settings
        SET setting_value = ?
        WHERE id_boss = ? AND setting_key = ?
    ");

    $stmt->execute([$setting_value, $id_boss, $setting_key]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            "status" => "success",
            "message" => "تم تحديث الإعداد بنجاح"
        ]);
    } else {
        echo json_encode([
            "status" => "fail",
            "message" => "لم يتم العثور على الإعداد أو لم يحدث أي تغيير"
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        "status" => "fail",
        "message" => "حدث خطأ: " . $e->getMessage()
    ]);
}
