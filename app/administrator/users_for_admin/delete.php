<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

include ROOT_PATH . 'connect.php';

// دالة لحذف الملف
function deleteFile($path, $imagename) {
    $file = $path . "/" . $imagename;
    if (file_exists($file)) {
        unlink($file);
    }
}

$user_id   = filterRequest("user_id");
$imagename = filterRequest("imagename");

// 1. جلب كل المرضى المرتبطين بالمستخدم
$stmt = $con->prepare("SELECT patient_id FROM patients WHERE user_patient = ?");
$stmt->execute([$user_id]);
$patients = $stmt->fetchAll(PDO::FETCH_COLUMN);

// 2. جلب كل علاجات المرضى
$treatment_ids = [];
if (!empty($patients)) {
    $in  = str_repeat('?,', count($patients) - 1) . '?';
    $stmt = $con->prepare("SELECT treatment_id FROM treatment WHERE patient_treatment IN ($in)");
    $stmt->execute($patients);
    $treatment_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// 3. حذف الصور من السيرفر
if (!empty($treatment_ids)) {
    $in  = str_repeat('?,', count($treatment_ids) - 1) . '?';
    $stmt = $con->prepare("SELECT image_path FROM treatment_images WHERE treatment_id IN ($in)");
    $stmt->execute($treatment_ids);
    $images = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($images as $imagePath) {
        $fullPath = "../" . $imagePath;
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }
}

// حذف المستخدم
$stmt = $con->prepare("DELETE FROM `users` WHERE user_id = ?");
$stmt->execute([$user_id]);
$count = $stmt->rowCount();

if ($count > 0) {
    if (!empty($imagename)) {
        deleteFile("../uplod", $imagename);
    }

    echo json_encode(array("status" => "success"));
} else {
    echo json_encode(array("status" => "fail"));
}
