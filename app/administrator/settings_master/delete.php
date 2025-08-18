<?php
header("Content-Type: application/json");
include ROOT_PATH . 'connect.php';

$setting_key = filterRequest("setting_key");

if (!$setting_key) {
    echo json_encode([
        "status" => "fail",
        "message" => "Missing required field: setting_key"
    ]);
    exit;
}

try {
    $stmt = $con->prepare("DELETE FROM settings_master WHERE setting_key = ?");
    $stmt->execute([$setting_key]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            "status" => "success",
            "message" => "تم حذف الإعداد بنجاح"
        ]);
    } else {
        echo json_encode([
            "status" => "fail",
            "message" => "الإعداد غير موجود"
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        "status" => "fail",
        "message" => $e->getMessage()
    ]);
}
