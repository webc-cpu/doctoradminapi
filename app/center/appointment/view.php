<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include ROOT_PATH . 'connect.php'; // ربط الاتصال

// هذا الفيو يعمل حسب الطلب يعني يفلتر البيانات حسب الطلب حسب البوس او السكرتيرا او اليوزر او الموعد
// appointment_id فقط الموعد المحدد
// user_id فقط مواعيد اليوزر
// boss_secretary فقط مواعيد الخاصة بالسكرتيرة الخاصة بالبوس
// secretary_id فقط مواعيد السكرتيرة
// جلب الفلاتر



$appointment_id  = filterRequest("appointment_id");
$user_id         = filterRequest("user_id");
$secretary_id    = filterRequest("secretary_id");
$boss_secretary  = filterRequest("boss_secretary");

// بناء الاستعلام والباراميترات حسب الطلب
$sql = "SELECT 
            a.appointment_id,
            a.user_id,
            u.user_name,
            a.secretary_id,
            a.patient_name,
            a.phone_number,
            s.secretary_name,
            a.appointment_date,
            a.appointment_note,
            a.created_at,
            a.boss_secretary
        FROM appointments a
        JOIN users u ON a.user_id = u.user_id
        JOIN secretaries s ON a.secretary_id = s.secretary_id
        WHERE 1=1 ";
$params = [];

if ($appointment_id) {
    $sql .= " AND a.appointment_id = ? ";
    $params[] = $appointment_id;
} else {
    if ($user_id) {
        $sql .= " AND a.user_id = ? ";
        $params[] = $user_id;
    }
    if ($secretary_id) {
        $sql .= " AND a.secretary_id = ? ";
        $params[] = $secretary_id;
    }
    if ($boss_secretary) {
        $sql .= " AND a.boss_secretary = ? ";
        $params[] = $boss_secretary;
    }
}

$sql .= " ORDER BY a.appointment_date DESC ";

$stmt = $con->prepare($sql);
$stmt->execute($params);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($appointments) {
    echo json_encode(["status" => "success", "data" => $appointments]);
} else {
    echo json_encode(["status" => "fail", "message" => "لا توجد مواعيد حسب المعطيات المدخلة"]);
}


