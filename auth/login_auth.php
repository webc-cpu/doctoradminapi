<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");



if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . '/../');
}

// ربط الاتصال
include ROOT_PATH . 'connect.php';
include ROOT_PATH . '/auth/jwt_handler.php';
require_once ROOT_PATH . 'middleware/rate_limit.php';

// استدعاء jwt_handler


$input = json_decode(file_get_contents("php://input"), true);

$email    = $_POST["email"] ?? $_GET["email"] ?? $input["email"] ?? null;
$password = $_POST["password"] ?? $_GET["password"] ?? $input["password"] ?? null;

if (!$email || !$password) {
    echo json_encode(["status" => "fail", "message" => "الرجاء إدخال البريد الإلكتروني وكلمة المرور"]);
    exit;
}

// دالة مساعدة لفك التشفير أو المقارنة المباشرة
function verifyPassword($inputPassword, $storedPassword) {
    return password_verify($inputPassword, $storedPassword) || $inputPassword === $storedPassword;
}

function generateUserToken($data, $role) {
    return generateJWT([
        'id'    => $data['user_id'] ?? $data['id_boss'] ?? $data['secretary_id'] ?? $data['id_Administrator'] ?? $data['visitor_id'] ?? null,
        'email' => $data['user_email'] ?? $data['boss_email'] ?? $data['secretary_email'] ?? $data['Administrator_email'] ?? $data['visitor_email'] ?? null,
        'role'  => $role
    ]);
}

// 1. users
$stmt = $con->prepare("SELECT * FROM users WHERE user_email = ?");
$stmt->execute([$email]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);
if ($data && verifyPassword($password, $data['user_password'])) {

    if (!empty($data['boss_user'])) {
        $token = generateUserToken($data, 'user');
        echo json_encode(["status" => "success", "type" => "user", "data" => $data, "token" => $token]);
        exit;
    }

    $email_verified = $data['email_verified'] ?? 1;

    if ($email_verified == 0) {
        echo json_encode([
            "status" => "denied",
            "type" => "user",
            "message" => "لم يتم تفعيل الحساب، الرجاء التحقق من بريدك الإلكتروني"
        ]);
        exit;
    }

    $startDate = new DateTime($data['user_start_date']);
    $endDate   = new DateTime($data['user_end_date']);
    $today     = new DateTime();

    $dayDiff = $startDate->diff($endDate)->days;
    $status  = "";

    if ($endDate > $today) {
        $status = ($dayDiff == 3) ? "مفعل ك ضيف" : "مفعل";
    } else {
        $status = ($dayDiff == 3) ? "يرجى الاشتراك" : "يرجى تجديد الاشتراك";
    }

    if ($status == "مفعل" || $status == "مفعل ك ضيف") {
        $token = generateUserToken($data, 'user');
        echo json_encode(["status" => "success", "type" => "user", "data" => $data, "message" => $status, "token" => $token]);
    } else {
        echo json_encode([
            "status" => "denied",
            "type" => "user",
            "message" => $status,
            "user_id" => $data['user_id']
        ]);
    }

    exit;
}

// 2. boss
$stmt = $con->prepare("SELECT * FROM boss WHERE boss_email = ?");
$stmt->execute([$email]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);
if ($data && verifyPassword($password, $data['boss_password'])) {
    $email_verified = $data['email_verified'];

    if ($email_verified == 0) {
        echo json_encode([
            "status" => "denied",
            "type" => "boss",
            "message" => "لم يتم تفعيل الحساب، الرجاء التحقق من بريدك الإلكتروني"
        ]);
        exit;
    }

    $startDate = new DateTime($data['boss_start_date']);
    $endDate   = new DateTime($data['boss_end_date']);
    $today     = new DateTime();

    $dayDiff = $startDate->diff($endDate)->days;
    $status  = "";

    if ($endDate > $today) {
        $status = ($dayDiff == 3) ? "مفعل ك ضيف" : "مفعل";
    } else {
        $status = ($dayDiff == 3) ? "يرجى الاشتراك" : "يرجى تجديد الاشتراك";
    }

    if ($status == "مفعل" || $status == "مفعل ك ضيف") {
        $token = generateUserToken($data, 'boss');
        echo json_encode(["status" => "success", "type" => "boss", "data" => $data, "message" => $status, "token" => $token]);
    } else {
        echo json_encode([
            "status" => "denied",
            "type" => "boss",
            "message" => $status,
            "id_boss" => $data['id_boss']
        ]);
    }

    exit;
}

// 3. secretaries
$stmt = $con->prepare("SELECT * FROM secretaries WHERE secretary_email = ?");
$stmt->execute([$email]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);
if ($data && verifyPassword($password, $data['secretary_password'])) {
    $token = generateUserToken($data, 'secretary');
    echo json_encode(["status" => "success", "type" => "secretary", "data" => $data, "token" => $token]);
    exit;
}

// 4. administrator
$stmt = $con->prepare("SELECT * FROM administrator WHERE Administrator_email = ?");
$stmt->execute([$email]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);
if ($data && verifyPassword($password, $data['Administrator_Password'])) {
    $responseData = [
        'id_Administrator' => $data['id_Administrator'],
        'Administrator_naime' => $data['Administrator_naime'],
        'Administrator_email' => $data['Administrator_email']
    ];

    $token = generateUserToken($data, 'administrator');
    echo json_encode([
        "status" => "success",
        "type" => "administrator",
        "data" => $responseData,
        "token" => $token
    ]);
    exit;
}

// 5. visitor
$stmt = $con->prepare("SELECT * FROM visitors WHERE visitor_email = ?");
$stmt->execute([$email]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);
if ($data && verifyPassword($password, $data['visitor_password'])) {
    $email_verified = $data['email_verified'] ?? 1;

    if ($email_verified == 0) {
        echo json_encode([
            "status" => "denied",
            "type" => "visitor",
            "message" => "لم يتم تفعيل البريد الإلكتروني بعد"
        ]);
        exit;
    }

    $token = generateUserToken($data, 'visitor');
    echo json_encode([
        "status" => "success",
        "type" => "visitor",
        "data" => $data,
        "token" => $token
    ]);
    exit;
}

echo json_encode(["status" => "fail", "message" => "فشل تسجيل الدخول، تحقق من البريد الإلكتروني وكلمة المرور"]);
