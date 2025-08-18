<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST");

include ROOT_PATH . 'connect.php';

$payment_id    = filterRequest("payment_id");
$amount        = filterRequest("amount");
$notes         = filterRequest("notes");
$payment_date  = filterRequest("payment_date"); // اختياري

if (!$payment_id || !$amount) {
    echo json_encode([
        "status" => "error",
        "message" => "payment_id and amount are required."
    ]);
    exit;
}

try {
    $fields  = "amount = ?, notes = ?";
    $params  = [$amount, $notes];

    if ($payment_date) {
        $fields .= ", payment_date = ?";
        $params[] = $payment_date;
    }

    $params[] = $payment_id; // لشرط WHERE

    $stmt = $con->prepare("UPDATE payments SET $fields WHERE payment_id = ?");
    $result = $stmt->execute($params);

    if ($result && $stmt->rowCount() > 0) {
        // جلب patient_id الخاص بالدفعة لتحديث الإحصائيات
        $stmtPatient = $con->prepare("SELECT patient_id FROM payments WHERE payment_id = ?");
        $stmtPatient->execute([$payment_id]);
        $patient_id = $stmtPatient->fetchColumn();

        if ($patient_id) {
            // استدعاء الدالة الموجودة في connect.php لتحديث الإحصائيات
            $updateResult = updatePatientStatistics($con, $patient_id);

            if ($updateResult === true) {
                echo json_encode([
                    "status" => "success",
                    "message" => "Payment updated and statistics updated successfully."
                ]);
            } else {
                echo json_encode([
                    "status" => "error",
                    "message" => "Payment updated but failed to update statistics: " . $updateResult
                ]);
            }
        } else {
            echo json_encode([
                "status" => "warning",
                "message" => "Payment updated but patient_id not found."
            ]);
        }
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "No changes made or payment not found."
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
}
