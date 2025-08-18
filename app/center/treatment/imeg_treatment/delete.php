<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST");

include ROOT_PATH . 'connect.php';
include_once ROOT_PATH . 'functions.php';

// استقبال الـ id فقط
$id = filterRequest("id");

if (!$id) {
    echo json_encode(["status" => "fail", "message" => "معرّف الصورة مفقود"]);
    exit;
}

// جلب بيانات الصورة والعلاج والمريض
$stmt = $con->prepare("
    SELECT 
        ti.image_path, 
        ti.treatment_id,
        t.patient_treatment, 
        t.user_treatment, 
        p.patient_name, 
        p.patient_card
    FROM treatment_images ti
    JOIN treatment t ON ti.treatment_id = t.treatment_id
    JOIN patients p ON t.patient_treatment = p.patient_id
    WHERE ti.id = ?
");
$stmt->execute([$id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    echo json_encode(["status" => "fail", "message" => "الصورة غير موجودة"]);
    exit;
}

// حذف الملف من السيرفر
$relativePath = $data['image_path'];
$fullPath = $_SERVER['DOCUMENT_ROOT'] . "/doctoradminapi/" . $relativePath;

if (file_exists($fullPath)) {
    if (!unlink($fullPath)) {
        echo json_encode(["status" => "fail", "message" => "فشل في حذف الملف من السيرفر"]);
        exit;
    }
} else {
    error_log("⚠️ ملف الصورة غير موجود فعليًا: $fullPath");
}

// حذف من قاعدة البيانات
$con->prepare("DELETE FROM treatment_images WHERE id = ?")->execute([$id]);

// تسجيل اللوج
$logStmt = $con->prepare("
    INSERT INTO treatment_logs (treatment_id, patient_id, user_id, action, details, log_date)
    VALUES (?, ?, ?, ?, ?, NOW())
");

$logStmt->execute([
    $data['treatment_id'],
    $data['patient_treatment'],
    $data['user_treatment'],
    'حذف صورة',
    "تم حذف للمريض : {$data['patient_name']} - البطاقة: {$data['patient_card']}"
]);

echo json_encode(["status" => "success", "message" => "✅ تم حذف الصورة بنجاح"]);
