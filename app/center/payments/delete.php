<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST");

include ROOT_PATH . 'connect.php';

$payment_id = filterRequest("payment_id");

if (!$payment_id) {
    echo json_encode([
        "status" => "error",
        "message" => "payment_id is required."
    ]);
    exit;
}

try {
    // جلب patient_id قبل الحذف عشان نستخدمه بعدين
    $stmtPatient = $con->prepare("SELECT patient_id FROM payments WHERE payment_id = ?");
    $stmtPatient->execute([$payment_id]);
    $patient_id = $stmtPatient->fetchColumn();

    if (!$patient_id) {
        echo json_encode([
            "status" => "error",
            "message" => "Payment not found or no associated patient."
        ]);
        exit;
    }

    // حذف الدفعة
    $stmt = $con->prepare("DELETE FROM payments WHERE payment_id = ?");
    $result = $stmt->execute([$payment_id]);

    if ($result && $stmt->rowCount() > 0) {
        // تحديث إحصائيات المريض بعد حذف الدفعة
        $updateResult = updatePatientStatistics($con, $patient_id);

        if ($updateResult === true) {
            echo json_encode([
                "status" => "success",
                "message" => "Payment deleted and statistics updated successfully."
            ]);
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Payment deleted but failed to update statistics: " . $updateResult
            ]);
        }
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Payment not found or already deleted."
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
}
