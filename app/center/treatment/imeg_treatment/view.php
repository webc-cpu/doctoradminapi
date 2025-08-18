<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header('Content-Type: application/json');

include ROOT_PATH . 'connect.php';

define("IMAGE_BASE_URL", "http://we-bc.atwebpages.com/doctoradminapi/");

$treatment_id = filterRequest("treatment_id");

if (!$treatment_id) {
    echo json_encode([
        "status" => "fail",
        "message" => "treatment_id is required"
    ]);
    exit;
}

// جلب الصور المرتبطة بالعلاج مع اسم الصورة
$stmt = $con->prepare("SELECT id, image_path, image_name FROM treatment_images WHERE treatment_id = ?");
$stmt->execute([$treatment_id]);
$images_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$images = array_map(function($img) {
    $image_name_from_path = basename($img['image_path']);
    return [
        "id" => $img['id'],
        "url" => IMAGE_BASE_URL . "get_image.php?img=" . urlencode($image_name_from_path),
        "image_name" => $img['image_name'] ?? $image_name_from_path  // اسم الصورة من القاعدة أو من الرابط إذا فارغ
    ];
}, $images_data);

echo json_encode([
    "status" => "success",
    "images" => $images
]);
