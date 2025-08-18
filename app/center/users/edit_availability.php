<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

include ROOT_PATH . 'connect.php';

$availability_id = filterRequest("availability_id");  // معرف السطر
$start_time      = filterRequest("start_time");       // وقت البداية الجديد (اختياري)
$end_time        = filterRequest("end_time");         // وقت النهاية الجديد (اختياري)
$is_available    = filterRequest("is_available");     // 1 أو 0 (اختياري)

function fixTimeFormat($time) {
    // لو الوقت بصيغة 12 ساعة مع AM/PM
    if (preg_match('/^(1[0-2]|0?[1-9]):([0-5][0-9])\s?(AM|PM)$/i', trim($time))) {
        return date("H:i:s", strtotime($time));
    }
    // لو الوقت بصيغة "12" أو "1" نضيف ":00:00"
    if (preg_match('/^\d{1,2}$/', $time)) {
        return sprintf('%02d:00:00', (int)$time);
    }
    // لو الوقت بصيغة "12:30" نضيف ":00" للثواني إذا مفقود
    if (preg_match('/^\d{1,2}:\d{2}$/', $time)) {
        return $time . ':00';
    }
    return $time; // غيرها كما هي
}


// جلب السطر الحالي
$stmtCheck = $con->prepare("SELECT * FROM user_availability WHERE availability_id = ?");
$stmtCheck->execute([$availability_id]);
$current = $stmtCheck->fetch(PDO::FETCH_ASSOC);

if (!$current) {
    echo json_encode(["status" => "fail", "message" => "سطر التوافر غير موجود"]);
    exit;
}

// تعويض القيم لو لم ترسل، نبقي القيم القديمة
$start_time   = !empty($start_time) ? fixTimeFormat($start_time) : $current['start_time'];
$end_time     = !empty($end_time) ? fixTimeFormat($end_time) : $current['end_time'];
$is_available = isset($is_available) ? (int)$is_available : $current['is_available'];

// تحديث البيانات بدون تغيير اسم اليوم
$stmtUpdate = $con->prepare("UPDATE user_availability SET start_time = ?, end_time = ?, is_available = ? WHERE availability_id = ?");
$updated = $stmtUpdate->execute([$start_time, $end_time, $is_available, $availability_id]);

if ($updated) {
    echo json_encode(["status" => "success", "message" => "تم تحديث التوافر بنجاح"]);
} else {
    echo json_encode(["status" => "fail", "message" => "حدث خطأ أثناء التحديث"]);
}
