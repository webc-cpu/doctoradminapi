<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
include ROOT_PATH . 'connect.php';

$user_id       = filterRequest("user_id");
$user_name     = filterRequest("user_name");
$user_role     = filterRequest("user_role");
$user_email    = filterRequest("user_email");
$user_password = filterRequest("user_password");

// جلب البيانات الحالية من القاعدة
$stmt = $con->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$currentData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$currentData) {
    echo json_encode(["status" => "fail", "message" => "المستخدم غير موجود"]);
    exit;
}

// دالة للتحقق إذا القيمة فارغة أو null
function isEmptyOrNull($value) {
    return !isset($value) || $value === null || trim($value) === "";
}

// تحقق من الإيميل إذا تغيّر، وتأكد ما يكون مكرر بقاعدة البيانات
if (!isEmptyOrNull($user_email) && $user_email !== $currentData['user_email']) {
    $stmtCheckEmail = $con->prepare("
        SELECT 'users' AS table_name FROM users WHERE user_email = ?
        UNION
        SELECT 'boss' AS table_name FROM boss WHERE boss_email = ?
        UNION
        SELECT 'secretaries' AS table_name FROM secretaries WHERE secretary_email = ?
    ");
    $stmtCheckEmail->execute([$user_email, $user_email, $user_email]);
    $emailExists = $stmtCheckEmail->fetch(PDO::FETCH_ASSOC);

    if ($emailExists) {
        echo json_encode([
            "status" => "fail",
            "message" => "البريد الإلكتروني مستخدم بالفعل في جدول " . $emailExists['table_name']
        ]);
        exit;
    }
}

// تعويض القيم الفارغة بالقيم الحالية
$user_name  = !isEmptyOrNull($user_name) ? $user_name : $currentData['user_name'];
$user_role  = !isEmptyOrNull($user_role) ? $user_role : $currentData['user_role'];
$user_email = !isEmptyOrNull($user_email) ? $user_email : $currentData['user_email'];

// إذا أرسل كلمة مرور جديدة، شفّرها، وإلا خذ القديمة
if (!isEmptyOrNull($user_password)) {
    $user_password = password_hash($user_password, PASSWORD_DEFAULT);
} else {
    $user_password = $currentData['user_password'];
}

// تحديث البيانات بدون صورة
$stmt = $con->prepare("UPDATE users SET
    user_name = ?,
    user_role = ?,
    user_email = ?,
    user_password = ?
    WHERE user_id = ?
");

$stmt->execute([$user_name, $user_role, $user_email, $user_password, $user_id]);

$count = $stmt->rowCount();

if ($count > 0) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "success", "message" => "لا توجد تغييرات"]);
}
