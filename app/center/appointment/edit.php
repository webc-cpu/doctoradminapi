<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

include ROOT_PATH . 'connect.php'; // ربط الاتصال




$appointment_id = filterRequest("appointment_id");
$user_id = filterRequest("user_id");
$patient_name           = filterRequest("patient_name");
$phone_number           = filterRequest("phone_number");
$appointment_date = filterRequest("appointment_date");
$appointment_time = filterRequest("appointment_time");
$appointment_note = filterRequest("appointment_note");

// أولاً، جب البيانات الحالية للسكرتيرة
$stmtSelect = $con->prepare("SELECT * FROM `appointments` WHERE `appointment_id` = ?");
$stmtSelect->execute([$appointment_id]);
$currentData = $stmtSelect->fetch(PDO::FETCH_ASSOC);

if (!$currentData) {
    echo json_encode(["status" => "fail", "message" => "الموعد غير موجود"]);
    exit;
}

// لو القيمة المرسلة فاضية أو null، استعمل القيمة القديمة
$user_id = !empty($user_id) ? $user_id : $currentData['user_id'];
$patient_name = !empty($patient_name) ? $patient_name : $currentData['patient_name'];
$phone_number = !empty($phone_number) ? $phone_number : $currentData['phone_number'];
$appointment_date = !empty($appointment_date) ? $appointment_date : $currentData['appointment_date'];
$appointment_time = !empty($appointment_time) ? $appointment_time : $currentData['appointment_time'];
$appointment_note = !empty($appointment_note) ? $appointment_note : $currentData['appointment_note'];

// نفذ التحديث
$stmtUpdate = $con->prepare("UPDATE `appointments` SET
    `user_id` = ?,
    `patient_name` = ?,
    `phone_number` = ?,
    `appointment_date` = ?,
    `appointment_time` = ?,
    `appointment_note` = ?
    WHERE `appointment_id` = ?
");

$stmtUpdate->execute([$user_id, $patient_name, $phone_number, $appointment_date, $appointment_time, $appointment_note, $appointment_id]);

if ($stmtUpdate->rowCount() > 0) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "fail", "message" => "لم يتم تعديل أي بيانات"]);
}
