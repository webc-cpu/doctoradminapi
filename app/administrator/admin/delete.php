<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

include ROOT_PATH . 'connect.php'; // ربط الاتصال

// حذف ملف صورة من السيرفر
function deleteFile($path, $imagename) {
    $file = $path . "/" . $imagename;
    if (file_exists($file)) {
        unlink($file);
    }
}

$boss_id   = filterRequest("id_boss");
$imagename = filterRequest("imagename");

// جلب كل الصور التابعة لعلاجات المرضى المرتبطين بالبوس
$stmt = $con->prepare("
    SELECT ti.image_path
    FROM treatment_images ti
    JOIN treatment t ON ti.treatment_id = t.treatment_id
    JOIN patients p ON t.patient_treatment = p.patient_id
    JOIN users u ON p.user_patient = u.user_id
    WHERE u.boss_user = ?
");
$stmt->execute([$boss_id]);
$images = $stmt->fetchAll(PDO::FETCH_COLUMN);

// حذف الصور من السيرفر
foreach ($images as $imagePath) {
    $fullPath = "../" . $imagePath;
    if (file_exists($fullPath)) {
        unlink($fullPath);
    }
}

// حذف البوس (وكل شيء متعلق فيه بينحذف تلقائيًا بفضل ON DELETE CASCADE)
$stmt = $con->prepare("DELETE FROM boss WHERE id_boss = ?");
$stmt->execute([$boss_id]);

if ($stmt->rowCount() > 0) {
    // حذف صورة البوس إن وجدت
    if (!empty($imagename)) {
        deleteFile("../uplod", $imagename);
    }

    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "fail"]);
}
