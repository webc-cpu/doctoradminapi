<?php

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

include ROOT_PATH . 'connect.php'; // الاتصال وقيم filterRequest

// توحيد المتغيرات (مشان يقبل هيك أو هيك)
$id_boss = filterRequest("id_boss") ?: filterRequest("boss_secretary");
$user_id = filterRequest("user_id") ?: filterRequest("user_secretary");

if (!empty($id_boss)) {
    // جلب السكرتيرات المرتبطين بالبوس
    $stmt = $con->prepare("SELECT * FROM secretaries WHERE boss_secretary = ?");
    $stmt->execute([$id_boss]);

} elseif (!empty($user_id)) {
    // جلب السكرتيرات المرتبطين باليوزر
    $stmt = $con->prepare("SELECT * FROM secretaries WHERE user_secretary = ?");
    $stmt->execute([$user_id]);

} else {
    echo json_encode(["status" => "fail", "message" => "يرجى إرسال معرف البوس أو اليوزر"]);
    exit;
}

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
$count = $stmt->rowCount();

if ($count > 0) {
    echo json_encode(["status" => "success", "data" => $data]);
} else {
    echo json_encode(["status" => "fail", "message" => "لا توجد سكرتيرات"]);
}
