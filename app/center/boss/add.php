<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");

include ROOT_PATH . 'connect.php'; // ربط الاتصال

// استقبال البيانات مع إعطاء تاريخ اليوم كافتراضي للـ boss_start_date
$boss_name       = filterRequest("boss_name");
$boss_start_date = filterRequest("boss_start_date") ?: date('Y-m-d');
$boss_end_date   = filterRequest("boss_end_date");
$max_users       = filterRequest("max_users");
$boss_email      = filterRequest("boss_email");
$boss_password   = filterRequest("boss_password");

// مصفوفة لتجميع الحقول الناقصة
$missing_fields = [];

if (!$boss_name)       $missing_fields[] = "boss_name";
if (!$max_users)       $missing_fields[] = "max_users";
if (!$boss_email)      $missing_fields[] = "boss_email";
if (!$boss_password)   $missing_fields[] = "boss_password";

if (count($missing_fields) > 0) {
    echo json_encode([
        "status" => "fail",
        "message" => "Missing required fields: " . implode(", ", $missing_fields)
    ]);
    exit;
}

// التحقق إذا الإيميل موجود مسبقًا
$checkStmt = $con->prepare("SELECT COUNT(*) FROM boss WHERE boss_email = ?");
$checkStmt->execute([$boss_email]);
$emailExists = $checkStmt->fetchColumn();

if ($emailExists > 0) {
    echo json_encode(["status" => "fail", "message" => "البريد الإلكتروني مستخدم من قبل"]);
    exit;
}

// تشفير كلمة المرور
$hashed_password = password_hash($boss_password, PASSWORD_DEFAULT);

// إذا ما أرسل تاريخ نهاية، نحطه بعد 3 أيام
if (!$boss_end_date) {
    $boss_end_date = date('Y-m-d', strtotime($boss_start_date . ' +3 days'));
}

// تحديد الحالة
$today = date('Y-m-d');
$day_diff = (strtotime($boss_end_date) - strtotime($boss_start_date)) / (60 * 60 * 24);

if ($day_diff == 3) {
    $is_active = 'مفعل كضيف';
} elseif ($boss_end_date >= $today) {
    $is_active = 'مفعل';
} else {
    $is_active = 'يرجى الاشتراك';
}

try {
    $stmt = $con->prepare("
        INSERT INTO `boss` 
        (`boss_name`, `boss_start_date`, `boss_end_date`, `is_active`, `max_users`, `boss_email`, `boss_password`) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$boss_name, $boss_start_date, $boss_end_date, $is_active, $max_users, $boss_email, $hashed_password]);

    if ($stmt->rowCount() > 0) {
        $boss_id = $con->lastInsertId(); // ← جلب ID البوس يلي انضاف
        
        insertDefaultSettingsForBoss($boss_id, $con);

        
        updateBossStatus($con, $boss_id); // ← تحديث الحالة وعدد اليوزر

        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "fail", "message" => "Insert failed"]);
    }

} catch (PDOException $e) {
    echo json_encode(["status" => "fail", "message" => $e->getMessage()]);
}
