<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

include ROOT_PATH . 'connect.php';

$user_name         = filterRequest("user_name");
$user_role         = filterRequest("user_role") ?: "طبيب في المركز"; // القيمة الافتراضية
$user_email        = filterRequest("user_email");
$user_password_raw = filterRequest("user_password");
$boss_user         = filterRequest("boss_user");

if (!filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        "status" => "fail",
        "message" => "البريد الإلكتروني غير صالح"
    ]);
    exit;
}

$pattern = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/";
if (!preg_match($pattern, $user_password_raw)) {
    echo json_encode([
        "status" => "fail",
        "message" => "كلمة المرور يجب أن تكون 8 أحرف على الأقل وتحتوي على أحرف كبيرة وصغيرة وأرقام ورموز"
    ]);
    exit;
}

$user_password = password_hash($user_password_raw, PASSWORD_DEFAULT);

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
    $role_id = 1;
    if (!empty($user_role)) {
        $roleStmt = $con->prepare("SELECT role_id FROM roles WHERE role_name = ?");
        $roleStmt->execute([$user_role]);
        $role = $roleStmt->fetch(PDO::FETCH_ASSOC);
        if ($role) {
            $role_id = $role["role_id"];
        }
    }

    $stmt = $con->prepare("
        INSERT INTO `users`(`user_name`, `user_role`, `role_id`, `user_email`, `user_password`, `boss_user`,`is_active`, `email_verified`)
        VALUES (?, ?, ?, ?, ?, ?,'مفعل' , 1)
    ");
    $stmt->execute([$user_name, $user_role, $role_id, $user_email, $user_password, $boss_user]);

    if ($stmt->rowCount() > 0) {
        $new_user_id = $con->lastInsertId();

        // إدخال التوافر الكامل لكل الأيام من الساعة 00:00 للساعة 23:59
        $days = ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
        $availabilityStmt = $con->prepare("
            INSERT INTO user_availability (user_id, day_of_week, start_time, end_time)
            VALUES (?, ?, '00:00:00', '23:59:59')
        ");
        foreach ($days as $day) {
            $availabilityStmt->execute([$new_user_id, $day]);
        }

        updateBossStatus($con, $boss_user);

        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "fail", "message" => "فشل في إضافة المستخدم"]);
    }

} catch (PDOException $e) {
    $errorMsg = $e->getMessage();

    if (str_contains($errorMsg, "عدد المستخدمين وصل للحد الأقصى")) {
        echo json_encode([
            "status" => "fail",
            "message" => "عذرًا، لا يمكنك إضافة المزيد من المستخدمين حالياً."
        ]);
    } else {
        echo json_encode([
            "status" => "fail",
            "message" => "حدث خطأ أثناء تنفيذ العملية: $errorMsg"
        ]);
    }
}
