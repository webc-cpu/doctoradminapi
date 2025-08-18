<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// السماح فقط بطلبات POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'fail', 'message' => 'يجب أن يكون الطلب POST']);
    exit;
}

// قراءة اسم الصورة من JSON
$data = json_decode(file_get_contents('php://input'), true);
$img = basename($data['img'] ?? '');

if (!$img) {
    echo json_encode(['status' => 'fail', 'message' => 'لم يتم ارسال اسم الصورة']);
    exit;
}

$imagePath = __DIR__ . "/uploads/medicines/" . $img;

if (!file_exists($imagePath)) {
    echo json_encode(['status' => 'fail', 'message' => 'الصورة غير موجودة']);
    exit;
}

$mime = mime_content_type($imagePath);
$base64 = base64_encode(file_get_contents($imagePath));

echo json_encode([
    'status' => 'success',
    'mime' => $mime,
    'base64' => $base64
]);
exit;



