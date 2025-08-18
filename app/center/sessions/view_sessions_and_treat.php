<?php
include ROOT_PATH . 'connect.php'; // ربط الاتصال
// يحتوي على الاتصال + دوال المساعدة مثل filterRequest و updateBossStatus

// استقبال user_id مع التحقق
$user_id = filterRequest("user_id");

if (empty($user_id)) {
    echo json_encode([
        "status" => "fail",
        "message" => "user_id is required"
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// جلب الجلسات المرتبطة بالمستخدم مرتبة من الأحدث
$stmt = $con->prepare("SELECT * FROM `sessions` WHERE `user_id` = ? ORDER BY `session_date` DESC");
$stmt->execute([$user_id]);

$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($sessions)) {
    foreach ($sessions as &$session) {
        $session_id = $session['session_id'];

        // جلب العلاجات المرتبطة بكل جلسة
        $stmt_treatment = $con->prepare("SELECT * FROM `treatment` WHERE `session_id` = ?");
        $stmt_treatment->execute([$session_id]);
        $treatment = $stmt_treatment->fetchAll(PDO::FETCH_ASSOC);

        $session['treatment'] = $treatment; // إضافة العلاجات ضمن الجلسة
    }

    echo json_encode([
        "status" => "success",
        "count" => count($sessions),
        "data" => $sessions
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        "status" => "fail",
        "message" => "ما في جلسات مرتبطة بهذا المستخدم"
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
?>
