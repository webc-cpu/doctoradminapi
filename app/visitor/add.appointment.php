<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

include ROOT_PATH . 'connect.php';

$user_id       = filterRequest("user_id");
$id_visitor    = filterRequest("id_visitor");
$date          = filterRequest("appointment_date");
$time          = filterRequest("appointment_time");
$appointment_note = filterRequest("appointment_note");

try {
    // 1. تحقق من وجود الزائر
    $stmtVisitor = $con->prepare("SELECT visitor_name, visitor_phone FROM visitors WHERE id_visitor = ?");
    $stmtVisitor->execute([$id_visitor]);
    $visitorData = $stmtVisitor->fetch(PDO::FETCH_ASSOC);

    if (!$visitorData) {
        echo json_encode(["status" => "fail", "message" => "الزائر غير موجود"]);
        exit;
    }

    $patient_name = $visitorData['visitor_name'];
    $phone_number = $visitorData['visitor_phone'];

    // 2. تحقق من وجود موعد بنفس التاريخ والوقت لنفس اليوزر
    $stmtCheck = $con->prepare("SELECT * FROM appointments WHERE user_id = ? AND appointment_date = ? AND appointment_time = ?");
    $stmtCheck->execute([$user_id, $date, $time]);

    if ($stmtCheck->rowCount() > 0) {
        echo json_encode(["status" => "fail", "message" => "هذا الموعد محجوز مسبقاً"]);
        exit;
    }

    // 3. جلب البوس التابع لليوزر
    $stmtBoss = $con->prepare("SELECT boss_user FROM users WHERE user_id = ?");
    $stmtBoss->execute([$user_id]);
    $userData = $stmtBoss->fetch(PDO::FETCH_ASSOC);
    $boss_id = $userData ? $userData['boss_user'] : null;

    // 4. إدخال الموعد
    $stmt = $con->prepare("INSERT INTO appointments 
        (user_id, boss_secretary, patient_name, phone_number, appointment_date, appointment_time, appointment_note) 
        VALUES (?, ?, ?, ?, ?, ?, ?)"
    );

    $stmt->execute([
        $user_id,
        $boss_id,
        $patient_name,
        $phone_number,
        $date,
        $time,
        $appointment_note
    ]);

    echo json_encode(["status" => "success", "message" => "تم إضافة الموعد بنجاح"]);

} catch (PDOException $e) {
    echo json_encode([
        "status" => "fail",
        "message" => "خطأ في السيرفر: " . $e->getMessage()
    ]);
}
