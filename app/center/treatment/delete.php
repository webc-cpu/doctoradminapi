<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

include ROOT_PATH . 'connect.php';
include_once ROOT_PATH . 'functions.php'; // تأكد أنو الدالة موجودة هون

// جلب treatment_id من الطلب
$treatment_id = filterRequest("treatment_id");
if (!$treatment_id) {
    echo json_encode(["status" => "fail", "message" => "معرّف العلاج مطلوب"]);
    exit;
}

// جلب بيانات العلاج
$stmt = $con->prepare("
    SELECT treatment_name, patient_treatment, user_treatment 
    FROM treatment 
    WHERE treatment_id = ?
");
$stmt->execute([$treatment_id]);
$treatment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$treatment) {
    echo json_encode(["status" => "fail", "message" => "العلاج غير موجود"]);
    exit;
}

$treatment_name    = $treatment['treatment_name'];
$patient_id        = $treatment['patient_treatment'];
$user_id           = $treatment['user_treatment'];

// جلب بيانات المريض
$stmt = $con->prepare("SELECT patient_name, patient_card FROM patients WHERE patient_id = ?");
$stmt->execute([$patient_id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);
$patient_name = $patient['patient_name'] ?? 'اسم المريض غير معروف';
$patient_card = $patient['patient_card'] ?? 'بدون رقم بطاقة';

// سجل اللوج قبل الحذف
$action  = "حذف";
$details = "تم حذف علاج: $treatment_name للمريض: $patient_name - رقم البطاقة: $patient_card";

$stmt_log = $con->prepare("
    INSERT INTO treatment_logs 
      (treatment_id, user_id, patient_id, action, details, log_date)
    VALUES (?, ?, ?, ?, ?, NOW())
");
$stmt_log->execute([
    $treatment_id,
    $user_id,
    $patient_id,
    $action,
    $details
]);

// حذف ملفات الصور المرتبطة
$stmt = $con->prepare("SELECT image_path FROM treatment_images WHERE treatment_id = ?");
$stmt->execute([$treatment_id]);
$images = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($images as $img) {
    $file = __DIR__ . "/../" . $img;
    if (file_exists($file)) {
        unlink($file);
    }
}

// حذف سجلات الصور من قاعدة البيانات
$con->prepare("DELETE FROM treatment_images WHERE treatment_id = ?")
    ->execute([$treatment_id]);

// حذف سجل العلاج
$stmt = $con->prepare("DELETE FROM treatment WHERE treatment_id = ?");
$stmt->execute([$treatment_id]);

if ($stmt->rowCount() > 0) {
    // ✅ تحديث الرصيد
    updatePatientTheRest($con, $patient_id);

    // ✅ تحديث إحصائيات المريض
    updatePatientStatistics($con, $patient_id);

    echo json_encode(["status" => "success", "message" => "تم الحذف بنجاح"]);
} else {
    echo json_encode(["status" => "fail", "message" => "فشل في حذف العلاج"]);
}
