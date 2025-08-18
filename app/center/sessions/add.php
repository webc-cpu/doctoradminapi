<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

include ROOT_PATH . 'connect.php'; // ربط الاتصال
// يحتوي على الاتصال + دوال المساعدة مثل filterRequest و updateBossStatus

$session_name = filterRequest("session_name");
$session_date = filterRequest("session_date");
$session_note = filterRequest("session_note");
$patient_id   = filterRequest("patient_id");
$is_finished  = filterRequest("is_finished");

// إذا ما وصل session_date أو كان فاضي، حط تاريخ اليوم
if (!$session_date || empty($session_date)) {
    $session_date = date("Y-m-d");
}

// إذا ما وصل is_finished أو القيمة مش 1 خليه 0
$is_finished = ($is_finished == 1) ? 1 : 0;

// جلب user_id من جدول المرضى حسب patient_id
$stmt_user = $con->prepare("SELECT user_patient FROM patients WHERE patient_id = ?");
$stmt_user->execute([$patient_id]);
$user = $stmt_user->fetch(PDO::FETCH_ASSOC);

// التأكد من وجود المريض
if ($user) {
    $user_id = $user['user_patient'];

    $stmt = $con->prepare("INSERT INTO sessions (session_name, session_date, session_note, patient_id, user_id, is_finished) VALUES (?, ?, ?, ?, ?, ?)");
    $isInserted = $stmt->execute([$session_name, $session_date, $session_note, $patient_id, $user_id, $is_finished]);

    if ($isInserted) {
        $session_id = $con->lastInsertId();
        echo json_encode([
            "status" => "success",
            "session_id" => $session_id
        ]);
    } else {
        echo json_encode(["status" => "fail", "message" => "insert_failed"]);
    }

} else {
    echo json_encode(["status" => "fail", "message" => "patient_not_found"]);
}
