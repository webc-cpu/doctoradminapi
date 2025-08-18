<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST");
header('Content-Type: application/json');

include ROOT_PATH . 'connect.php';
include_once ROOT_PATH . 'functions.php';

$id = filterRequest('id');
$newName = filterRequest('image_name');

if (!$id || !$newName) {
    echo json_encode([
        "status" => "fail",
        "message" => "معرف الصورة واسم الصورة الجديد مطلوبين"
    ]);
    exit;
}

// تنظيف الاسم الجديد بحيث يسمح بالعربية، الأرقام، الحروف، _ و -
$newNameClean = preg_replace('/[^\p{Arabic}\p{L}\p{N}_\-]/u', '_', $newName);

$stmt = $con->prepare("UPDATE treatment_images SET image_name = ? WHERE id = ?");
$updated = $stmt->execute([$newNameClean, $id]);

if ($updated) {
    echo json_encode([
        "status" => "success",
        "message" => "تم تحديث اسم الصورة بنجاح",
        "new_name" => $newNameClean
    ]);
} else {
    echo json_encode([
        "status" => "fail",
        "message" => "فشل تحديث اسم الصورة"
    ]);
}
?>
