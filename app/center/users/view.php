<?php

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

include ROOT_PATH . 'connect.php';

$user_id = filterRequest("user_id");

// جلب بيانات المستخدم
$stmtUser = $con->prepare("SELECT * FROM users WHERE user_id = ?");
$stmtUser->execute([$user_id]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(["status" => "fail", "message" => "المستخدم غير موجود"]);
    exit;
}

// جلب بيانات التوافر مع id
$stmtAvailability = $con->prepare("
    SELECT 
        availability_id AS availability_id,
        day_of_week AS day, 
        start_time, 
        end_time,
        is_available 
    FROM user_availability 
    WHERE user_id = ? 
    ORDER BY FIELD(day_of_week, 'الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت')
");
$stmtAvailability->execute([$user_id]);
$availability = $stmtAvailability->fetchAll(PDO::FETCH_ASSOC);

// تحويل الوقت لصيغة 12 ساعة
foreach ($availability as &$slot) {
    $slot['start_time'] = date("h:i A", strtotime($slot['start_time']));
    $slot['end_time']   = date("h:i A", strtotime($slot['end_time']));
}

// إرسال النتيجة
echo json_encode([
    "status" => "success",
    "user" => $user,
    "availability" => $availability
]);
