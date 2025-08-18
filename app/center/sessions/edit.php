<?php
include ROOT_PATH . 'connect.php'; // ربط الاتصال

$session_id = filterRequest("session_id");

// جلب البيانات الحالية من قاعدة البيانات
$stmtSelect = $con->prepare("SELECT * FROM sessions WHERE session_id = ?");
$stmtSelect->execute([$session_id]);
$currentData = $stmtSelect->fetch(PDO::FETCH_ASSOC);

// التحقق من وجود الجلسة
if (!$currentData) {
    echo json_encode(["status" => "fail", "message" => "الجلسة غير موجودة"]);
    exit();
}

// الحصول على القيم الجديدة أو استخدام القديمة
$session_name = filterRequest("session_name") ?: $currentData['session_name'];
$session_date = filterRequest("session_date") ?: $currentData['session_date'];
$session_note = filterRequest("session_note") ?: $currentData['session_note'];
$is_finished = isset($_POST['is_finished']) ? filterRequest("is_finished") : $currentData['is_finished'];


// تنفيذ التحديث
$stmtUpdate = $con->prepare("
    UPDATE `sessions` 
    SET 
        `session_name` = ?, 
        `session_date` = ?, 
        `session_note` = ?,
        `is_finished`  = ?
    WHERE session_id = ?
");

$stmtUpdate->execute([
    $session_name,
    $session_date,
    $session_note,
    $is_finished,
    $session_id
]);

// لا تعتمد على rowCount، اعتبر العملية ناجحة دائماً إذا لم يحدث خطأ
if ($stmtUpdate) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "fail"]);
}
