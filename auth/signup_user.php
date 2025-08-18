<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . '/../');
}

require_once ROOT_PATH . 'libs/PHPMailer/PHPMailer.php'; 
require_once ROOT_PATH . 'libs/PHPMailer/SMTP.php'; 
require_once ROOT_PATH . 'libs/PHPMailer/Exception.php'; 

include ROOT_PATH . 'connect.php';

$user_name     = filterRequest("user_name");
$user_email    = filterRequest("user_email");
$user_password = filterRequest("user_password");
$user_role     = filterRequest("user_role") ?: "طبيب";

if (empty($user_email) || empty($user_password)) {
    echo json_encode(["status" => "fail", "message" => "يرجى تعبئة البريد الإلكتروني وكلمة المرور"]);
    exit;
}

if (!filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["status" => "fail", "message" => "صيغة البريد الإلكتروني غير صحيحة"]);
    exit;
}

if (
    strlen($user_password) < 8 ||
    !preg_match('/[A-Z]/', $user_password) ||
    !preg_match('/[a-z]/', $user_password) ||
    !preg_match('/[0-9]/', $user_password) ||
    !preg_match('/[\W]/', $user_password)
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

$emailCheckStmt->execute([$user_email, $user_email, $user_email, $user_email]);

if ($emailCheckStmt->fetchColumn() > 0) {
    echo json_encode(["status" => "fail", "message" => "❌ البريد الإلكتروني مستخدم بالفعل في النظام"]);
    exit;
}

$hashedPassword = password_hash($user_password, PASSWORD_DEFAULT);

$role_id = 2;
$roleStmt = $con->prepare("SELECT role_id FROM roles WHERE role_name = ?");
$roleStmt->execute([$user_role]);
$role = $roleStmt->fetch(PDO::FETCH_ASSOC);
if ($role) {
    $role_id = $role["role_id"];
}

$start_date   = date("Y-m-d");
$end_date     = date("Y-m-d", strtotime("+3 days"));
$verify_token = bin2hex(random_bytes(16));

$stmt = $con->prepare("
    INSERT INTO users (
        user_name, user_email, user_password, user_role, role_id,
        email_verified, user_start_date, user_end_date, verify_token
    ) VALUES (?, ?, ?, ?, ?, 0, ?, ?, ?)
");

$stmt->execute([
    $user_name, $user_email, $hashedPassword, $user_role, $role_id,
    $start_date, $end_date, $verify_token
]);

if ($stmt->rowCount() > 0) {
    $lastId = $con->lastInsertId();

    $days = ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
    $availabilityStmt = $con->prepare("
        INSERT INTO user_availability (user_id, day_of_week, start_time, end_time)
        VALUES (?, ?, '00:00:00', '23:59:59')
    ");
    foreach ($days as $day) {
        $availabilityStmt->execute([$lastId, $day]);
    }

    updateUserStatusById($con, $lastId);

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'webc.cpu@gmail.com';
        $mail->Password   = 'wusb lisb xejt wkhg';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('webc.cpu@gmail.com', '=?UTF-8?B?' . base64_encode('web.c') . '?=');
        $mail->addAddress($user_email, '=?UTF-8?B?' . base64_encode($user_name) . '?=');

        $verifyLink = "http://we-bc.atwebpages.com/doctoradminapi/auth/verify.php?token=$verify_token";

        $mail->isHTML(true);
        $mail->Subject = '=?UTF-8?B?' . base64_encode('تفعيل حساب المستخدم') . '?=';

        $mail->Body = "
            <div style='font-family: Arial; direction: rtl;'>
                <h2>مرحباً $user_name</h2>
                <p>لتفعيل حسابك، اضغط على الزر التالي:</p>
                <a href='$verifyLink' style='
                    display: inline-block;
                    padding: 10px 15px;
                    background-color: #007bff;
                    color: white;
                    text-decoration: none;
                    border-radius: 5px;'>تفعيل الحساب</a>
                <p>صلاحية الحساب 3 أيام - ينتهي في $end_date</p>
                <p>إذا لم تقم بالتسجيل، تجاهل هذه الرسالة.</p>
            </div>
        ";

        $mail->send();
        echo json_encode(["status" => "success", "message" => "✅ تم إنشاء الحساب. تحقق من بريدك لتفعيله."]);
    } catch (Exception $e) {
        echo json_encode(["status" => "fail", "message" => "⚠️ تم إنشاء الحساب ولكن لم يتم إرسال الإيميل: {$mail->ErrorInfo}"]);
    }
} else {
    echo json_encode(["status" => "fail", "message" => "حدث خطأ أثناء إنشاء الحساب"]);
}
