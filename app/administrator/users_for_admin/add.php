<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

include ROOT_PATH . 'connect.php';

$user_name           = filterRequest("user_name");
$user_role           = filterRequest("user_role") ?: "طبيب"; // القيمة الافتراضية
$user_email          = filterRequest("user_email");
$user_password_raw   = filterRequest("user_password"); // كلمة المرور بصيغتها الخام
$user_start_date     = filterRequest("user_start_date");
$user_end_date       = filterRequest("user_end_date");
$email_verified      = filterRequest("email_verified");

// تحقق من البريد الإلكتروني
if (!filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        "status" => "fail",
        "message" => "البريد الإلكتروني غير صالح"
    ]);
    exit;
}

// تحقق من كلمة المرور بصيغتها الخام
$pattern = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/";
if (!preg_match($pattern, $user_password_raw)) {
    echo json_encode([
        "status" => "fail",
        "message" => "كلمة المرور يجب أن تكون 8 أحرف على الأقل وتحتوي على أحرف كبيرة وصغيرة وأرقام ورموز"
    ]);
    exit;
}

// تشفير كلمة المرور
$user_password = password_hash($user_password_raw, PASSWORD_DEFAULT);

// تحقق من عدم تكرار البريد الإلكتروني
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
        "message" => "البريد الإلكتروني مستخدم بالفعل"
    ]);
    exit;
}

try {
    $role_id = 2; // القيمة الافتراضية لطبيب

    // جلب role_id من قاعدة البيانات إذا تم تحديد user_role
    if (!empty($user_role)) {
        $roleStmt = $con->prepare("SELECT role_id FROM roles WHERE role_name = ?");
        $roleStmt->execute([$user_role]);
        $role = $roleStmt->fetch(PDO::FETCH_ASSOC);

        if ($role) {
            $role_id = $role["role_id"];
        }
    }

    // إدخال المستخدم مع الحقول بدون is_active
    $stmt = $con->prepare("
        INSERT INTO `users` (
            `user_name`, `user_role`, `role_id`, `user_email`, `user_password`,
            `user_start_date`, `user_end_date`, `email_verified`
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $user_name, $user_role, $role_id, $user_email, $user_password,
        $user_start_date, $user_end_date, $email_verified
    ]);

    if ($stmt->rowCount() > 0) {
        $lastId = $con->lastInsertId();
        updateUserStatusById($con, $lastId);  // <=== هنا الاستدعاء

        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "fail", "message" => "فشل في إضافة المستخدم"]);
    }

} catch (PDOException $e) {
    echo json_encode([
        "status" => "fail",
        "message" => "حدث خطأ أثناء تنفيذ العملية: " . $e->getMessage()
    ]);
}
