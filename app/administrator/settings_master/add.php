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

// التحقق من وجود المفتاح مسبقاً
$stmtCheck = $con->prepare("SELECT COUNT(*) FROM settings_master WHERE setting_key = ?");
$stmtCheck->execute([$setting_key]);
if ($stmtCheck->fetchColumn() > 0) {
    echo json_encode([
        "status" => "fail",
        "message" => "هذا المفتاح موجود مسبقاً"
    ]);
    exit;
}

try {
    $stmt = $con->prepare("
        INSERT INTO settings_master (setting_key, label, default_value)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$setting_key, $label, $default_value]);

    echo json_encode([
        "status" => "success",
        "message" => "تم إضافة الإعداد بنجاح"
    ]);
} catch (PDOException $e) {
    echo json_encode([
        "status" => "fail",
        "message" => $e->getMessage()
    ]);
}
