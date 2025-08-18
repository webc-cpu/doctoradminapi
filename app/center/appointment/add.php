<?php

// اذا ما بعتت يوزر ايدي ابدا بيتم اخد اليوزر يلي السكرتيرا بتتبعلو وازا بعتت يوزر ايدي بتتحقق انو اليوزر تابع لنفس البوس تبعها


header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

include ROOT_PATH . 'connect.php'; // ربط الاتصال

$secretary_id      = filterRequest("secretary_id");
$user_id           = filterRequest("user_id"); // (اختياري)
$patient_name      = filterRequest("patient_name");
$phone_number      = filterRequest("phone_number");
$appointment_date  = filterRequest("appointment_date");
$appointment_time  = filterRequest("appointment_time");
$appointment_note  = filterRequest("appointment_note") ?: "لا يوجد";

// جلب بيانات السكرتيرة (نحتاج boss_id و user_secretary)
$stmt = $con->prepare("SELECT boss_secretary, user_secretary FROM secretaries WHERE secretary_id = ?");
$stmt->execute([$secretary_id]);
$sec = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sec) {
    echo json_encode(["status" => "fail", "message" => "السكرتيرة غير موجودة"]);
    exit;
}

$boss_id = $sec["boss_secretary"];

// إذا ما تم إرسال user_id → خذو من user_secretary بدون التحقق من البوس
if (!$user_id) {
    if (!empty($sec["user_secretary"])) {
        $user_id = $sec["user_secretary"];
    } else {
        echo json_encode(["status" => "fail", "message" => "لم يتم إرسال user_id ولا يوجد user مرتبط بالسكرتيرة"]);
        exit;
    }
} else {
    // إذا تم إرسال user_id → تحقق أن اليوزر يتبع نفس البوس المرتبط بالسكرتيرة (مش شرط يكون user_secretary نفسه)
    $stmt = $con->prepare("SELECT user_id FROM users WHERE user_id = ? AND boss_user = ?");
    $stmt->execute([$user_id, $boss_id]);
    $userCheck = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$userCheck) {
        echo json_encode(["status" => "fail", "message" => "اليوزر لا يتبع لنفس البوس المرتبط بالسكرتيرة"]);
        exit;
    }
}

// إدخال الموعد
$stmt = $con->prepare("
    INSERT INTO appointments (user_id, patient_name, phone_number, appointment_date, appointment_time, appointment_note, secretary_id, boss_secretary)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->execute([$user_id, $patient_name, $phone_number, $appointment_date, $appointment_time, $appointment_note, $secretary_id, $boss_id]);

if ($stmt->rowCount() > 0) {
    echo json_encode(["status" => "success", "message" => "تم إضافة الموعد"]);
} else {
    echo json_encode(["status" => "fail", "message" => "فشل في إضافة الموعد"]);
}
