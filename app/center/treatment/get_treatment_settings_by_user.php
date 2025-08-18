<?php
// يقوم بجلب العلاجات الخاصة بالبوس الذي اضاف اليوزر 

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include ROOT_PATH . 'connect.php';

$user_id = filterRequest("user_id");

// جلب id البوس المرتبط بالمستخدم
$stmt = $con->prepare("SELECT boss_user FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$boss_id = $stmt->fetchColumn();

if (!$boss_id) {
    echo json_encode(["status" => "fail", "message" => "المستخدم غير مرتبط ببوس"]);
    exit;
}

// جلب العلاجات الخاصة بالبوس
$stmt = $con->prepare("SELECT treatment_name, treatment_price FROM treatment_settings WHERE id_boss = ?");
$stmt->execute([$boss_id]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(["status" => "success", "data" => $data]);
