<?php

// 🧩 ضم ملف الكونفج أولاً (يعرف ROOT_PATH و غيره)
require_once __DIR__ . '/config.php'; // هذا هو جذر المشروع فعلياً

// 🟢 التعامل مع OPTIONS قبل أي شيء
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-API-KEY, X-Requested-With");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header('Content-Type: application/json');
    http_response_code(200);
    exit();
}

// 🟢 إعدادات CORS للطلبات العادية
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-API-KEY, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header('Content-Type: application/json');

// ✅ تحميل دوال مساعدة
require_once ROOT_PATH . 'functions.php';

// ✅ تحميل متغيرات .env
loadEnv(ROOT_PATH . '.env');

// ✅ إعدادات تسجيل الأخطاء
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', ROOT_PATH . 'logs/error.log');
error_reporting(E_ALL);

// ✅ الاتصال بقاعدة البيانات
$dsn      = "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8mb4";
$user     = $_ENV['DB_USER'];
$password = $_ENV['DB_PASS'];

$options = [
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
];

try {
    $con = new PDO($dsn, $user, $password, $options);
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("DB Connection failed: " . $e->getMessage());
    echo json_encode(["status" => "fail", "message" => "فشل الاتصال بقاعدة البيانات"]);
    exit;
}
