<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
include ROOT_PATH . 'connect.php';

$user_id           = filterRequest("user_id");
$user_name         = filterRequest("user_name");
$user_role         = filterRequest("user_role");
$user_email        = filterRequest("user_email");
$user_password     = filterRequest("user_password");
$email_verified    = filterRequest("email_verified");
$is_active         = filterRequest("is_active");
$user_start_date   = filterRequest("user_start_date");
$user_end_date     = filterRequest("user_end_date");

// جلب البيانات الحالية
$stmt = $con->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$currentData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$currentData) {
    echo json_encode(["status" => "fail", "message" => "المستخدم غير موجود"]);
    exit;
}

// دالة تحقق
function isEmptyOrNull($value) {
    return !isset($value) || $value === null || trim($value) === "";
}

// تحقق من الإيميل
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

// تعويض القيم الفارغة
$user_name        = !isEmptyOrNull($user_name)       ? $user_name       : $currentData['user_name'];
$user_role        = !isEmptyOrNull($user_role)       ? $user_role       : $currentData['user_role'];
$user_email       = !isEmptyOrNull($user_email)      ? $user_email      : $currentData['user_email'];
$email_verified   = isset($email_verified)           ? $email_verified  : $currentData['email_verified'];
$is_active        = !isEmptyOrNull($is_active)       ? $is_active       : $currentData['is_active'];
$user_start_date  = !isEmptyOrNull($user_start_date) ? $user_start_date : $currentData['user_start_date'];
$user_end_date    = !isEmptyOrNull($user_end_date)   ? $user_end_date   : $currentData['user_end_date'];

// ✅ تحقق من صحة is_active
$allowed_statuses = ['مفعل', 'مفعل ك ضيف', 'يرجى الاشتراك', 'يرجى تجديد الاشتراك'];
if (!in_array($is_active, $allowed_statuses)) {
    echo json_encode([
        "status" => "fail",
        "message" => "قيمة is_active غير مسموحة. القيم المسموحة: " . implode(', ', $allowed_statuses)
    ]);
    exit;
}

// كلمة المرور
if (!isEmptyOrNull($user_password)) {
    $user_password = password_hash($user_password, PASSWORD_DEFAULT);
} else {
    $user_password = $currentData['user_password'];
}

// تنفيذ التحديث
$stmt = $con->prepare("UPDATE users SET
    user_name = ?,
    user_role = ?,
    user_email = ?,
    user_password = ?,
    email_verified = ?,
    is_active = ?,
    user_start_date = ?,
    user_end_date = ?
    WHERE user_id = ?
");

$stmt->execute([
    $user_name,
    $user_role,
    $user_email,
    $user_password,
    $email_verified,
    $is_active,
    $user_start_date,
    $user_end_date,
    $user_id
]);

$count = $stmt->rowCount();

if ($count > 0) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "success", "message" => "لا توجد تغييرات"]);
}
