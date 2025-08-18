<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

include ROOT_PATH . 'connect.php'; // ربط الاتصال
// يحتوي على الاتصال + دوال المساعدة مثل filterRequest و updateBossStatus

$secretary_id = filterRequest("secretary_id");
$secretary_name = filterRequest("secretary_name");
$secretary_email = filterRequest("secretary_email");
$secretary_password = filterRequest("secretary_password");

// أولاً، جب البيانات الحالية للسكرتيرة
$stmtSelect = $con->prepare("SELECT * FROM `secretaries` WHERE `secretary_id` = ?");
$stmtSelect->execute([$secretary_id]);
$currentData = $stmtSelect->fetch(PDO::FETCH_ASSOC);

if (!$currentData) {
    echo json_encode(["status" => "fail", "message" => "السكرتيرة غير موجودة"]);
    exit;
}

// لو الإيميل تم تغييره، تأكد ما يكون مكرر بقاعدة البيانات
if (!empty($secretary_email) && $secretary_email !== $currentData['secretary_email']) {
    $stmtCheckEmail = $con->prepare("
        SELECT 'secretaries' AS table_name FROM secretaries WHERE secretary_email = ?
        UNION
        SELECT 'boss' AS table_name FROM boss WHERE boss_email = ?
        UNION
        SELECT 'users' AS table_name FROM users WHERE user_email = ?
    ");
    $stmtCheckEmail->execute([$secretary_email, $secretary_email, $secretary_email]);
    $emailExists = $stmtCheckEmail->fetch(PDO::FETCH_ASSOC);

    if ($emailExists) {
        echo json_encode([
            "status" => "fail",
            "message" => "البريد الإلكتروني مستخدم بالفعل في جدول " . $emailExists['table_name']
        ]);
        exit;
    }
}

// لو القيمة المرسلة فاضية أو null، استعمل القيمة القديمة
$secretary_name = !empty($secretary_name) ? $secretary_name : $currentData['secretary_name'];
$secretary_email = !empty($secretary_email) ? $secretary_email : $currentData['secretary_email'];

// تشفير كلمة المرور إذا تم إرسالها
if (!empty($secretary_password)) {
    $secretary_password = password_hash($secretary_password, PASSWORD_DEFAULT);
} else {
    $secretary_password = $currentData['secretary_password'];
}

// نفذ التحديث
$stmtUpdate = $con->prepare("UPDATE `secretaries` SET
    `secretary_name` = ?,
    `secretary_email` = ?,
    `secretary_password` = ?
    WHERE `secretary_id` = ?
");

$stmtUpdate->execute([$secretary_name, $secretary_email, $secretary_password, $secretary_id]);

if ($stmtUpdate->rowCount() > 0) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "fail", "message" => "لم يتم تعديل أي بيانات"]);
}
