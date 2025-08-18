<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST");

include ROOT_PATH . 'connect.php';
include_once ROOT_PATH . 'functions.php';

$treatment_id = filterRequest("treatment_id");

if (!$treatment_id) {
    echo json_encode(["status" => "fail", "message" => "رقم العلاج مفقود"]);
    exit;
}

$stmt = $con->prepare("
    SELECT t.patient_treatment, t.user_treatment, p.patient_name, p.patient_card 
    FROM treatment t 
    JOIN patients p ON t.patient_treatment = p.patient_id 
    WHERE t.treatment_id = ?
");
$stmt->execute([$treatment_id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    echo json_encode(["status" => "fail", "message" => "العلاج غير موجود"]);
    exit;
}

$patient_id   = $data['patient_treatment'];
$user_id      = $data['user_treatment'];
$patient_name = $data['patient_name'] ?? "";
$patient_card = $data['patient_card'] ?? "";

$uploadDir = $_SERVER['DOCUMENT_ROOT'] . "/doctoradminapi/uploads/medicines/";
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

$allUploaded = true;

foreach ($_FILES['images']['name'] as $index => $name) {
    $error   = $_FILES['images']['error'][$index];
    $tmpName = $_FILES['images']['tmp_name'][$index];

    if ($error !== UPLOAD_ERR_OK) {
        $allUploaded = false;
        break;
    }

    if (!mb_detect_encoding($name, 'UTF-8', true)) {
        $name = iconv('Windows-1256', 'UTF-8//IGNORE', $name);
    }

    $extension    = pathinfo($name, PATHINFO_EXTENSION);
    $filenameOnly = pathinfo($name, PATHINFO_FILENAME);

    $filenameOnly = preg_replace('/[^\p{Arabic}\p{L}\p{N}_\-.]/u', '_', $filenameOnly);
    $safeName     = uniqid() . "_" . $filenameOnly . "." . $extension;
    $imagePath    = $uploadDir . $safeName;

    if (!move_uploaded_file($tmpName, $imagePath)) {
        $fileContent = file_get_contents($tmpName);
        file_put_contents($imagePath, $fileContent);
    }

    if (file_exists($imagePath)) {
        $relativePath = "uploads/medicines/" . $safeName;

        // نحفظ الاسم العربي الأصلي إن وجد، وإلا نستخدم اسم الملف بعد الحفظ
        $originalName = !empty($filenameOnly) ? $filenameOnly . '.' . $extension : $safeName;

        $stmt = $con->prepare("INSERT INTO treatment_images (treatment_id, image_path, image_name) VALUES (?, ?, ?)");
        $stmt->execute([$treatment_id, $relativePath, $originalName]);

        // لوج
        $details = "تم رفع صورة جديدة للعلاج";
        if ($patient_name) {
            $details .= " للمريض: $patient_name";
            if ($patient_card) {
                $details .= " - رقم البطاقة: $patient_card";
            }
        }

        $stmt_log = $con->prepare("
            INSERT INTO treatment_logs (treatment_id, patient_id, user_id, action, details, log_date)
            VALUES (?, ?, ?, 'إضافة صورة جديدة', ?, NOW())
        ");
        $stmt_log->execute([$treatment_id, $patient_id, $user_id, $details]);
    } else {
        $allUploaded = false;
        break;
    }
}

if ($allUploaded) {
    echo json_encode(["status" => "success", "message" => "تم رفع الصور بنجاح"]);
} else {
    echo json_encode(["status" => "fail", "message" => "فشل في رفع بعض الصور"]);
}
