<?php
include ROOT_PATH . 'connect.php'; // الاتصال

// استقبال المدخلات
$secretary_name      = filterRequest("secretary_name");
$secretary_email     = filterRequest("secretary_email");
$secretary_password  = filterRequest("secretary_password");

// توحيد متغيرات البوس واليوزر
$boss_secretary = filterRequest("boss_secretary") ?: filterRequest("id_boss");
$user_secretary = filterRequest("user_secretary") ?: filterRequest("user_id");

// التحقق من صحة الإيميل
if (!filter_var($secretary_email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        "status" => "fail",
        "message" => "البريد الإلكتروني غير صالح"
    ]);
    exit;
}

// تحقق من كلمة المرور
$pattern = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/";
if (!preg_match($pattern, $secretary_password)) {
    echo json_encode([
        "status" => "fail",
        "message" => "كلمة المرور يجب أن تكون 8 أحرف على الأقل وتحتوي على أحرف كبيرة وصغيرة وأرقام ورموز"
    ]);
    exit;
}

// تأكد من عدم تكرار الإيميل
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

// تشفير كلمة المرور
$hashed_password = password_hash($secretary_password, PASSWORD_DEFAULT);

// تحقق من وجود واحد على الأقل: boss أو user
if (empty($boss_secretary) && empty($user_secretary)) {
    echo json_encode([
        "status" => "fail",
        "message" => "يجب تحديد إما معرف البوس أو اليوزر"
    ]);
    exit;
}

// تنفيذ الإدخال
$stmt = $con->prepare("
    INSERT INTO `secretaries`(`secretary_name`, `secretary_email`, `secretary_password`, `boss_secretary`, `user_secretary`)
    VALUES (?, ?, ?, ?, ?)
");
$result = $stmt->execute([
    $secretary_name,
    $secretary_email,
    $hashed_password,
    $boss_secretary,
    $user_secretary
]);

if ($result) {
    echo json_encode(["status" => "success"]);
} else {
    $errorInfo = $stmt->errorInfo();
    echo json_encode([
        "status" => "fail",
        "message" => "فشل في إضافة السكرتيرة",
        "error" => $errorInfo[2]
    ]);
}
