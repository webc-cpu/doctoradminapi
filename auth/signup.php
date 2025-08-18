<?php

// السماح بالوصول من أي مصدر
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");

// استدعاء PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . '/../');
}

require_once ROOT_PATH . 'libs/PHPMailer/PHPMailer.php'; 
require_once ROOT_PATH . 'libs/PHPMailer/SMTP.php'; 
require_once ROOT_PATH . 'libs/PHPMailer/Exception.php'; 

include ROOT_PATH . 'connect.php';

// استقبال وتنظيف البيانات
$boss_name     = filterRequest("boss_name");
$boss_email    = filterRequest("boss_email");
$boss_password = filterRequest("boss_password");

// التحقق من الحقول
if (empty($boss_email) || empty($boss_password)) {
    echo json_encode(["status" => "fail", "message" => "يرجى تعبئة البريد الإلكتروني وكلمة المرور"]);
    exit;
}

if (!filter_var($boss_email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["status" => "fail", "message" => "صيغة البريد الإلكتروني غير صحيحة"]);
    exit;
}

if (
    strlen($boss_password) < 8 ||
    !preg_match('/[A-Z]/', $boss_password) ||
    !preg_match('/[a-z]/', $boss_password) ||
    !preg_match('/[0-9]/', $boss_password) ||
    !preg_match('/[\W]/', $boss_password)
) {
    echo json_encode([
        "status" => "fail",
        "message" => "كلمة المرور يجب أن تكون 8 أحرف على الأقل وتحتوي على حرف كبير وصغير ورقم ورمز خاص"
    ]);
    exit;
}

// ✅ التحقق من وجود البريد الإلكتروني في جميع الجداول (مع معالجة collation)
$emailCheckStmt = $con->prepare("
    SELECT COUNT(*) FROM (
        SELECT CAST(boss_email AS CHAR CHARACTER SET utf8mb4) AS email FROM boss WHERE boss_email = ?
        UNION ALL
        SELECT CAST(user_email AS CHAR CHARACTER SET utf8mb4) AS email FROM users WHERE user_email = ?
        UNION ALL
        SELECT CAST(secretary_email AS CHAR CHARACTER SET utf8mb4) AS email FROM secretaries WHERE secretary_email = ?
        UNION ALL
        SELECT CAST(visitor_email AS CHAR CHARACTER SET utf8mb4) AS email FROM visitors WHERE visitor_email = ?
    ) AS all_emails
");

$emailCheckStmt->execute([$boss_email, $boss_email, $boss_email, $boss_email]);

if ($emailCheckStmt->fetchColumn() > 0) {
    echo json_encode(["status" => "fail", "message" => "❌ البريد الإلكتروني مستخدم بالفعل في النظام"]);
    exit;
}

// إنشاء رمز التحقق
$verify_token = bin2hex(random_bytes(16));

// تاريخ البداية والنهاية
$today = date("Y-m-d");
$end_date = date("Y-m-d", strtotime("+3 days"));

// إدخال البيانات
$stmt = $con->prepare("
    INSERT INTO boss (boss_name, boss_email, boss_password, verify_token, boss_start_date, boss_end_date)
    VALUES (?, ?, ?, ?, ?, ?)
");
$stmt->execute([$boss_name, $boss_email, $boss_password, $verify_token, $today, $end_date]);

if ($stmt->rowCount() > 0) {
    $lastId = $con->lastInsertId();

    require_once "../functions.php";
    insertDefaultSettingsForBoss($lastId, $con);
    updateBossStatus($con, $lastId);
}

// إرسال الإيميل
if ($stmt->rowCount() > 0) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'webc.cpu@gmail.com';
        $mail->Password   = 'wusb lisb xejt wkhg'; // كلمة مرور التطبيق
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('webc.cpu@gmail.com', '=?UTF-8?B?' . base64_encode('web.c') . '?=');
        $mail->addAddress($boss_email, '=?UTF-8?B?' . base64_encode($boss_name) . '?=');

        $verifyLink = "http://we-bc.atwebpages.com/doctoradminapi/auth/verify.php?token=$verify_token";

        $mail->isHTML(true);
        $mail->Subject = '=?UTF-8?B?' . base64_encode('تفعيل الحساب') . '?=';

        $mail->Body = "
            <div style='font-family: Arial; direction: rtl;'>
                <h2>مرحباً $boss_name</h2>
                <p>شكراً لتسجيلك. لتفعيل حسابك، اضغط على الزر أدناه:</p>
                <a href='$verifyLink' style='
                    display: inline-block;
                    padding: 10px 15px;
                    background-color: #28a745;
                    color: white;
                    text-decoration: none;
                    border-radius: 5px;'>تفعيل الحساب</a>
                <p>هذا حساب تجريبي لمدة 3 أيام ينتهي بتاريخ $end_date</p>
                <p>إذا لم تقم بإنشاء هذا الحساب، يمكنك تجاهل هذه الرسالة.</p>
            </div>
        ";

        $mail->send();
        echo json_encode(["status" => "success", "message" => "✅ تم إنشاء الحساب. تحقق من بريدك الإلكتروني لتفعيله."]);
    } catch (Exception $e) {
        echo json_encode(["status" => "fail", "message" => "⚠️ تم إنشاء الحساب ولكن فشل إرسال الإيميل: {$mail->ErrorInfo}"]);
    }
} else {
    echo json_encode(["status" => "fail", "message" => "حدث خطأ أثناء إنشاء الحساب"]);
}
