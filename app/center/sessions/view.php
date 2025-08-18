<?php
// include ROOT_PATH . 'connect.php'; // ربط الاتصال
// يحتوي على الاتصال + دوال المساعدة مثل filterRequest و updateBossStatus

// // استقبال user_id مع التحقق
// $user_id = filterRequest("user_id");

// if (empty($user_id)) {
//     echo json_encode([
//         "status" => "fail",
//         "message" => "user_id is required"
//     ]);
//     exit;
// }

// // جلب الجلسات المرتبطة بالمستخدم
// $stmt = $con->prepare("SELECT * FROM `sessions` WHERE `user_id` = ?");
// $stmt->execute([$user_id]);

// $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// if (!empty($data)) {
//     echo json_encode([
//         "status" => "success",
//         "count" => count($data), // عدد النتائج
//         "data" => $data
//     ]);
// } else {
//     echo json_encode([
//         "status" => "fail",
//         "message" => "ما في جلسات مرتبطة بهذا المستخدم"
//     ]);
// }


header("Cache-Control: no-cache, no-store, must-revalidate");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
include ROOT_PATH . 'connect.php';


$patient_id          = filterRequest("patient_id");




$stmt = $con->prepare("SELECT * FROM sessions  WHERE `patient_id` = ? ");

$stmt->execute(array($patient_id));

$data =$stmt ->fetchAll(PDO::FETCH_ASSOC);

$count = $stmt->rowCount();

if ($count > 0) {
  echo json_encode(array("status" => "success", "data" => $data));
}else{
  echo json_encode(array("status" => "fail"));
}
