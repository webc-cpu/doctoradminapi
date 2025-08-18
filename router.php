<?php

// ✅ السماح بطلبات OPTIONS (CORS Preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-API-KEY, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json");

// ✅ تعريف مسار المشروع
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . '/');
}

// ✅ استدعاء معدل الطلبات (rate limit) لو عندك
include_once ROOT_PATH . 'middleware/rate_limit.php';

// ✅ دالة قراءة ملف .env
function parseEnvFile($path) {
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $env = [];
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (!strpos($line, '=')) continue;
        list($key, $value) = explode('=', $line, 2);
        $env[trim($key)] = trim($value);
    }
    return $env;
}

// ✅ تحميل متغيرات .env
$envPath = ROOT_PATH . '.env';
$env = file_exists($envPath) ? parseEnvFile($envPath) : [];

// ✅ جلب حالة تفعيل حماية API Key من .env
$enableApiKeyCheck = true;
if (isset($env['API_KEY_CHECK_ENABLED'])) {
    $enableApiKeyCheck = filter_var($env['API_KEY_CHECK_ENABLED'], FILTER_VALIDATE_BOOLEAN);
}

// ✅ معالجة URI
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$basePath = '/doctoradminapi/';  // عدل حسب مسار مشروعك
if (strpos($uri, $basePath) === 0) {
    $uri = substr($uri, strlen($basePath));
}
$uri = trim($uri, '/');

// ✅ إذا كان الطلب مثل: index.php?url=...
if (strpos($uri, 'index.php') === 0) {
    $queryString = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
    $queryData = [];
    if ($queryString !== null) {
        parse_str($queryString, $queryData);
    }
    $request = $queryData['url'] ?? null;
} else {
    $request = $uri;
}

if (empty($request)) {
    echo json_encode(['message' => 'يرجى تحديد مسار الطلب']);
    exit;
}

// ✅ خريطة المسارات
$map = [
    'admin'              => 'app/administrator/admin',
    'permissions'       => 'app/administrator/admin/permissions',
    'settings_master'   => 'app/administrator/settings_master',
    'version'           => 'app/administrator/version',
    'version_for_admin' => 'app/administrator/version_for_admin',
    'users_for_admin'   => 'app/administrator/users_for_admin',
    'doctor'            => 'app/administrator/doctor',

    'appointment'       => 'app/center/appointment',
    'boss_settings'     => 'app/center/boss_settings',
    'expenses'          => 'app/center/expenses',
    'patients'          => 'app/center/patients',
    'secretary'         => 'app/center/secretary',
    'sessions'          => 'app/center/sessions',
    'treatment'         => 'app/center/treatment',
    'treatment_logs'    => 'app/center/treatment/treatment_logs',
    'treatment_settings'=> 'app/center/treatment_settings',
    'imeg_treatment'    => 'app/center/treatment/imeg_treatment',
    'users'             => 'app/center/users',
    'clinics_profiles'  => 'app/center/clinics_profiles',
    'payments'          => 'app/center/payments',
    'boss'          => 'app/center/boss',

    'visitor'           => 'app/visitor',
    'exports'           => 'app/exports',
    'view'              => 'app/view'
];

// ✅ تنظيف المسار
$sanitizedPath = preg_replace('/[^a-zA-Z0-9_\-\/\.آ-ی]/u', '', $request);

// ✅ تقسيم المسار
$parts = explode('/', $sanitizedPath);
$first = $parts[0] ?? '';
$rest = array_slice($parts, 1);

// ✅ تطبيق الخريطة إذا لزم
if (isset($map[$first])) {
    $sanitizedPath = $map[$first];
    if (!empty($rest)) {
        $sanitizedPath .= '/' . implode('/', $rest);
    }
}

// ✅ تحقق من المسار النهائي
if (!preg_match('/^[a-zA-Z0-9_\-\/\.آ-ی]+$/u', $sanitizedPath)) {
    http_response_code(400);
    echo json_encode(['message' => 'مسار غير صالح']);
    exit;
}

// ✅ إضافة امتداد .php إذا غير موجود
if (substr($sanitizedPath, -4) !== '.php') {
    $sanitizedPath .= '.php';
}

// ✅ إنشاء المسار الكامل
$fullPath = ROOT_PATH . $sanitizedPath;

// ✅ ملفات مستثناة من حماية الـ API Key
$excludedFiles = [
    'login_auth.php', 'generate_boss_excel.php', 'generate_user_excel.php',
    'generate_pdf_patient.php', 'signup.php', 'index.php',
    'signup_user.php', 'tree.php', 'get_image.php'
];

// ✅ مجلدات مستثناة من حماية الـ API Key
$excludedFolders = ['treatment', 'exports', 'auth','test'];

// ✅ اسم الملف الحالي
$currentFile = basename($sanitizedPath);

// ✅ تحقق إذا المسار يحتوي على مجلد مستثنى
$isInExcludedFolder = false;
foreach ($excludedFolders as $folder) {
    $folder = trim($folder, '/');
    if (preg_match("#(^|/){$folder}(/|$)#", $sanitizedPath)) {
        $isInExcludedFolder = true;
        break;
    }
}



// ✅ تنفيذ حماية الـ API Key إذا لزم الأمر
if ($enableApiKeyCheck && !in_array($currentFile, $excludedFiles) && !$isInExcludedFolder) {
    require_once ROOT_PATH . 'check_api_key.php';
}

// ✅ تنفيذ الملف المطلوب
if (file_exists($fullPath)) {
    require $fullPath;
} else {
    http_response_code(404);
    echo json_encode(['message' => 'الملف غير موجود']);
}
