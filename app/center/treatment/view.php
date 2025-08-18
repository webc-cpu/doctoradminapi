<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header('Content-Type: application/json');

include ROOT_PATH . 'connect.php';

// استلام treatment_id
$treatment_id = filterRequest("treatment_id");

if (!$treatment_id) {
    echo json_encode([
        "status" => "fail",
        "message" => "treatment_id is required"
    ]);
    exit;
}

// جلب بيانات العلاج
$stmt = $con->prepare("SELECT * FROM treatment WHERE treatment_id = ?");
$stmt->execute([$treatment_id]);
$treatment = $stmt->fetch(PDO::FETCH_ASSOC);

if ($treatment) {
    echo json_encode([
        "status" => "success",
        "data" => $treatment
    ]);
} else {
    echo json_encode([
        "status" => "fail",
        "message" => "No treatment found for this treatment_id"
    ]);
}










// header("Access-Control-Allow-Origin: *");
// header("Access-Control-Allow-Headers: Content-Type, Authorization");
// header("Access-Control-Allow-Methods: POST, OPTIONS");
// header('Content-Type: application/json');

// include ROOT_PATH . 'connect.php';

// define("IMAGE_BASE_URL", "http://we-bc.atwebpages.com/doctoradminapi/");

// // استلام treatment_id من الطلب بشكل آمن
// $treatment_id = filterRequest("treatment_id");

// if (!$treatment_id) {
//     echo json_encode([
//         "status" => "fail",
//         "message" => "treatment_id is required"
//     ], JSON_UNESCAPED_SLASHES);
//     exit;
// }

// // جلب بيانات العلاج حسب treatment_id
// $stmt = $con->prepare("SELECT * FROM treatment WHERE treatment_id = ?");
// $stmt->execute([$treatment_id]);
// $treatment = $stmt->fetch(PDO::FETCH_ASSOC);

// if ($treatment) {
//     // جلب الصور المرتبطة بالعلاج مع المعرف id
//     $img_stmt = $con->prepare("SELECT id, image_path FROM treatment_images WHERE treatment_id = ?");
//     $img_stmt->execute([$treatment_id]);
//     $images_data = $img_stmt->fetchAll(PDO::FETCH_ASSOC);

//     // تحويل كل صورة إلى مصفوفة تحتوي id و url
//     $images = array_map(function($img) {
//         $image_name = basename($img['image_path']);
//         return [
//             "id" => $img['id'],
//             "url" => IMAGE_BASE_URL . "get_image.php?img=" . urlencode($image_name)
//         ];
//     }, $images_data);

//     $treatment["images"] = $images;

//     echo json_encode([
//         "status" => "success",
//         "data" => $treatment
//     ], JSON_UNESCAPED_SLASHES);
// } else {
//     echo json_encode([
//         "status" => "fail",
//         "message" => "No treatment found for this treatment_id"
//     ], JSON_UNESCAPED_SLASHES);
// }
?>
