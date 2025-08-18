<?php

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-API-KEY, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header('Content-Type: application/json');

// استدعاء ملف الإعدادات لتعريف الثوابت
require_once __DIR__ . '/config.php';

// استدعاء ملف الاتصال بقاعدة البيانات
require_once ROOT_PATH . 'connect.php';

// استدعاء ملف الروتر (التوجيه)
require_once ROOT_PATH . 'router.php';
