<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

include ROOT_PATH . 'connect.php'; // ربط الاتصال
// يحتوي على الاتصال + دوال المساعدة مثل filterRequest و updateBossStatus

$patient_id = filterRequest("patient_id");

if (!$patient_id) {
    echo json_encode(array("status" => "fail", "message" => "لم يصل رقم المريض"));
    exit;
}

// 1. جلب جميع مسارات الصور المرتبطة بعلاجات المريض
$stmt = $con->prepare("SELECT image_path FROM treatment_images WHERE treatment_id IN (SELECT treatment_id FROM treatment WHERE patient_treatment = ?)");
$stmt->execute([$patient_id]);
$images = $stmt->fetchAll(PDO::FETCH_COLUMN);

$uploadDir = "../assets/medicines/";

// 2. حذف الصور من المجلد
foreach ($images as $image) {
    $imagePath = $uploadDir . basename($image);
    if (file_exists($imagePath)) {
        unlink($imagePath);
    }
}

// 3. حذف المريض
$stmt = $con->prepare("DELETE FROM `patients` WHERE patient_id = ?");
$stmt->execute([$patient_id]);

if ($stmt->rowCount() > 0) {
    echo json_encode(array("status" => "success"));
} else {
    echo json_encode(array("status" => "fail"));
}
?>

