<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// تضمين ملف الاتصال بقاعدة البيانات
include ROOT_PATH . 'connect.php'; // ربط الاتصال

try {
    // الجملة الرئيسية لنسخ القيم الافتراضية مع label
    $sql = "
        INSERT INTO boss_settings (id_boss, setting_key, setting_value, label)
        SELECT b.id_boss, sm.setting_key, sm.default_value, sm.label
        FROM boss b
        CROSS JOIN settings_master sm
        WHERE NOT EXISTS (
            SELECT 1 
            FROM boss_settings bs
            WHERE bs.id_boss = b.id_boss 
              AND bs.setting_key = sm.setting_key
        )
    ";
    
    // تحضير وتنفيذ الاستعلام
    $stmt = $con->prepare($sql);
    $stmt->execute();
    
    // عدد الصفوف التي أدخلت
    $count = $stmt->rowCount();
    
    echo json_encode([
        "status"    => "success",
        "inserted"  => $count,
        "message"   => "تمت مزامنة الإعدادات بنجاح."
    ]);
    
} catch (PDOException $e) {
    // في حال وقوع خطأ
    http_response_code(500);
    echo json_encode([
        "status"  => "error",
        "message" => "خطأ في التنفيذ: " . $e->getMessage()
    ]);
}
