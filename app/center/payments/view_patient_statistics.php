<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST");

include ROOT_PATH . 'connect.php';

// استقبال patient_id من الطلب
$patient_id = filterRequest("patient_id");

if (!$patient_id) {
    echo json_encode([
        "status" => "error",
        "message" => "الرقم التعريفي للمريض (patient_id) مطلوب"
    ]);
    exit;
}

try {
    // جلب بيانات الإحصائيات من الجدول
    $stmt = $con->prepare("SELECT * FROM patient_statistics WHERE patient_id = ?");
    $stmt->execute([$patient_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        echo json_encode([
            "status" => "success",
            "data" => $data
        ]);
    } else {
        echo json_encode([
            "status" => "empty",
            "message" => "لا يوجد بيانات إحصائية لهذا المريض"
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
}
