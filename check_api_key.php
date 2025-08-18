<?php

loadEnv(ROOT_PATH . '.env');
loadEnv(ROOT_PATH . 'functions.php');
require_once __DIR__ . '/auth/jwt_handler.php';

$api_key_expected = $_ENV['API_KEY'] ?? 'default_api_key';

$headers = getallheaders();
$api_key_received = '';
$auth_header = '';

// ✅ حاول أولاً تاخدهم من الهيدر
foreach ($headers as $key => $value) {
    $lower_key = strtolower($key);
    if ($lower_key === 'api-key') {
        $api_key_received = $value;
    } elseif ($lower_key === 'authorization') {
        $auth_header = $value;
    }
}

// ✅ إذا ما لقيتهم بالهيدر، خدهم من body/post/get
if (empty($api_key_received)) {
    $api_key_received = filterRequest("api_key");
}
if (empty($auth_header)) {
    $auth_header = filterRequest("token");
}

// ✅ تحقق من وجود API Key
if (empty($api_key_received)) {
    http_response_code(401);
    echo json_encode([
        "status" => "fail",
        "message" => "Missing API Key"
    ]);
    exit;
}

// ✅ تحقق من صحة API Key
if ($api_key_received !== $api_key_expected) {
    http_response_code(403);
    echo json_encode([
        "status" => "fail",
        "message" => "Access denied - Invalid API Key"
    ]);
    exit;
}

// ✅ تحقق من وجود Authorization token
if (empty($auth_header)) {
    http_response_code(401);
    echo json_encode([
        "status" => "fail",
        "message" => "Missing Authorization token"
    ]);
    exit;
}

// ✅ استخراج التوكن سواء بصيغة Bearer أو بدون
if (preg_match('/Bearer\s(\S+)/i', $auth_header, $matches)) {
    $jwt_token = $matches[1];
} else {
    $jwt_token = trim($auth_header);
}

// ✅ تحقق من صحة التوكن
try {
    $decoded = validateJWT($jwt_token);
    $GLOBALS['CURRENT_USER'] = $decoded->data;
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        "status" => "fail",
        "message" => "Invalid or expired token",
        "error" => $e->getMessage()
    ]);
    exit;
}
