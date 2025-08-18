<?php

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

include ROOT_PATH . 'connect.php'; // ربط الاتصال

$user_name = filterRequest("user_name"); // حقل البحث (اختياري)

if (!empty($user_name)) {
    // إذا تم إرسال الاسم
    $stmt = $con->prepare("
        SELECT * FROM users 
        WHERE (boss_user IS NULL OR boss_user = '') 
        AND user_name LIKE ?
    ");
    $searchTerm = "%" . $user_name . "%";
    $stmt->execute([$searchTerm]);
} else {
    // إذا ما تم إرسال الاسم
    $stmt = $con->prepare("
        SELECT * FROM users 
        WHERE boss_user IS NULL OR boss_user = ''
    ");
    $stmt->execute();
}

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
$count = $stmt->rowCount();

if ($count > 0) {
    echo json_encode(["status" => "success", "data" => $data]);
} else {
    echo json_encode(["status" => "fail"]);
}
