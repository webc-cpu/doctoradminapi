<?php

// السماح بالوصول من أي مصدر
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");

// استدعاء PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once ROOT_PATH . 'PHPMailer/PHPMailer.php';
require_once ROOT_PATH . 'PHPMailer/SMTP.php';
require_once ROOT_PATH . 'PHPMailer/Exception.php';

// تعريف ROOT_PATH إذا ما كان معرف
if (!defined("ROOT_PATH")) {
    define("ROOT_PATH", dirname(__DIR__) . "/");
}

// الاتصال بقاعدة البيانات
include ROOT_PATH . 'connect.php'; // ربط الاتصال

// استقبال وتنظيف البيانات
$visitor_name     = filterRequest("visitor_name");
$visitor_email    = filterRequest("visitor_email");
$visitor_phone    = filterRequest("visitor_phone");
$visitor_password = filterRequest("visitor_password");

// التحقق من الحقول الأساسية
if (empty($visitor_name) || empty($visitor_email) || empty($visitor_password)) {
    echo json_encode(["status" => "fail", "message" => "يرجى تعبئة الاسم والبريد الإلكتروني وكلمة المرور"]);
    exit;
}

if (!filter_var($visitor_email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["status" => "fail", "message" => "صيغة البريد الإلكتروني غير صحيحة"]);
    exit;
}

// التحقق من قوة كلمة المرور
if (
    strlen($visitor_password) < 8 ||
    !preg_match('/[A-Z]/', $visitor_password) ||
    !preg_match('/[a-z]/', $visitor_password) ||
    !preg_match('/[0-9]/', $visitor_password) ||
    !preg_match('/[\W]/', $visitor_password)
) {
    echo json_encode([
        "status" => "fail",
        "message" => "كلمة المرور يجب أن تكون 8 أحرف على الأقل وتحتوي على حرف كبير وصغير ورقم ورمز خاص"
    ]);
    exit;
}

// التحقق من وجود البريد الإلكتروني مسبقاً
$checkStmt = $con->prepare("SELECT COUNT(*) FROM visitors WHERE visitor_email = ?");
$checkStmt->execute([$visitor_email]);
if ($checkStmt->fetchColumn() > 0) {
    echo json_encode(["status" => "fail", "message" => "❌ البريد الإلكتروني مستخدم بالفعل"]);
    exit;
}

// إنشاء رمز التحقق
$verify_token = bin2hex(random_bytes(16));

// إدخال البيانات
$stmt = $con->prepare("
    INSERT INTO visitors (visitor_name, visitor_email, visitor_phone, visitor_password, verify_token)
    VALUES (?, ?, ?, ?, ?)
");

$executed = $stmt->execute([
    $visitor_name,
    $visitor_email,
    $visitor_phone ?: null,
    $visitor_password, // ممكن تستخدم password_hash() لو بدك تشفير
    $verify_token
]);

if ($executed) {
    // إرسال الإيميل
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
        $mail->addAddress($visitor_email, '=?UTF-8?B?' . base64_encode($visitor_name) . '?=');

        $verifyLink = "http://we-bc.atwebpages.com/doctoradminapi/auth/verify_visitor.php?token=$verify_token";

        $mail->isHTML(true);
        $mail->Subject = '=?UTF-8?B?' . base64_encode('تفعيل الحساب') . '?=';

        $mail->Body = "
            <div style='font-family: Arial; direction: rtl;'>
                <h2>مرحباً $visitor_name</h2>
                <p>شكراً لتسجيلك. لتفعيل حسابك، اضغط على الزر أدناه:</p>
                <a href='$verifyLink' style='
                    display: inline-block;
                    padding: 10px 15px;
                    background-color: #28a745;
                    color: white;
                    text-decoration: none;
                    border-radius: 5px;'>تفعيل الحساب</a>
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
