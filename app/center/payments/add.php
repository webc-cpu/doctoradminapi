<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST");

include ROOT_PATH . 'connect.php';

$patient_id = filterRequest("patient_id");
$amount = filterRequest("amount");
$registered_by_id = filterRequest("registered_by_id");
$notes = filterRequest("notes");
$payment_date = filterRequest("payment_date");

if (!$patient_id || !$amount || !$registered_by_id) {
    echo json_encode([
        "status" => "error",
        "message" => "patient_id, amount, and registered_by_id are required."
    ]);
    exit;
}

if (!$payment_date) {
    $payment_date = date('Y-m-d H:i:s');
}

try {
    $checkPatient = $con->prepare("SELECT COUNT(*) FROM patients WHERE patient_id = ?");
    $checkPatient->execute([$patient_id]);
    if ($checkPatient->fetchColumn() == 0) {
        echo json_encode([
            "status" => "error",
            "message" => "Patient ID does not exist."
        ]);
        exit;
    }

    $stmt = $con->prepare("INSERT INTO payments (patient_id, amount, registered_by_id, payment_date, notes) VALUES (?, ?, ?, ?, ?)");
    $result = $stmt->execute([$patient_id, $amount, $registered_by_id, $payment_date, $notes]);

    if ($result) {
        $updateResult = updatePatientStatistics($con, $patient_id);
        if ($updateResult === true) {
            echo json_encode([
                "status" => "success",
                "message" => "Payment added and statistics updated successfully."
            ]);
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Payment added but failed to update statistics: " . $updateResult
            ]);
        }
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Failed to add payment."
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
}
