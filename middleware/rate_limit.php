<?php
include_once ROOT_PATH . 'connect.php';

$ip = $_SERVER['REMOTE_ADDR'];
$limit = 100;
$window = 60; // ثانية

// 📦 تنظيف السجلات القديمة بطريقة ذكية
$cleanupFile = ROOT_PATH . 'rate_limit_cleanup.txt';
$now = time();
$cleanupInterval = 3600; // كل ساعة = 3600 ثانية

if (!file_exists($cleanupFile) || ($now - filemtime($cleanupFile)) > $cleanupInterval) {
    // حذف السجلات الأقدم من 10 دقايق
    try {
        $stmt = $con->prepare("DELETE FROM rate_limits WHERE last_request_time < NOW() - INTERVAL 10 MINUTE");
        $stmt->execute();
        // تحديث وقت آخر تنظيف
        file_put_contents($cleanupFile, "last cleanup: " . date("Y-m-d H:i:s"));
    } catch (Exception $e) {
        // اختياري: سجل الخطأ بمكان ثاني إذا بدك
    }
}

// 🛡️ تطبيق Rate Limiting الطبيعي
try {
    $stmt = $con->prepare("SELECT * FROM rate_limits WHERE ip_address = ?");
    $stmt->execute([$ip]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $last_time = strtotime($row['last_request_time']);
        if (($now - $last_time) < $window) {
            if ($row['request_count'] >= $limit) {
                http_response_code(429);
                echo json_encode(["error" => "Too many requests. Please try again later."]);
                exit;
            } else {
                $stmt = $con->prepare("UPDATE rate_limits SET request_count = request_count + 1 WHERE ip_address = ?");
                $stmt->execute([$ip]);
            }
        } else {
            $stmt = $con->prepare("UPDATE rate_limits SET request_count = 1, last_request_time = NOW() WHERE ip_address = ?");
            $stmt->execute([$ip]);
        }
    } else {
        $stmt = $con->prepare("INSERT INTO rate_limits (ip_address) VALUES (?)");
        $stmt->execute([$ip]);
    }
} catch (Exception $e) {
    // تجاهل أو سجل الخطأ
}
