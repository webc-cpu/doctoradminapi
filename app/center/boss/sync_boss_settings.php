<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
ini_set('default_charset', 'UTF-8'); // يضمن دعم الأحرف العربية

include ROOT_PATH . 'connect.php';

try {
   $sql = "
    SELECT 
        b.id_boss, 
        sm.setting_key, 
        sm.default_value, 
        sm.label
    FROM boss b
    CROSS JOIN settings_master sm
    WHERE NOT EXISTS (
        SELECT 1 
        FROM boss_settings bs
        WHERE bs.id_boss = b.id_boss 
          AND bs.setting_key = sm.setting_key
    )
";

$stmt = $con->prepare($sql);
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// اطبع النتائج وتأكد من محتوى label
echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
exit;


    $stmt = $con->prepare($sql);
    $stmt->execute();

    echo json_encode([
        "status"    => "success",
        "inserted"  => $stmt->rowCount(),
        "message"   => "تمت مزامنة الإعدادات ونسخ جميع الحقول، بما فيها الليبل بالعربي."
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "status"  => "error",
        "message" => "خطأ في التنفيذ: " . $e->getMessage()
    ]);
}
