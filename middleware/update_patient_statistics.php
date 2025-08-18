<?php

include_once ROOT_PATH . 'connect.php';

$patient_id = filterRequest("patient_id");

// استخدام الدوال من ملف functions.php
$total_cost = calculate_total_treatment_cost($con, $patient_id);
$total_paid = calculate_total_paid($con, $patient_id);

$remaining = $total_cost - $total_paid;
$is_paid   = $remaining <= 0 ? 1 : 0;

// تحقق إذا السطر موجود
$stmt = $con->prepare("SELECT COUNT(*) FROM patient_statistics WHERE patient_id = ?");
$stmt->execute([$patient_id]);
$exists = $stmt->fetchColumn();

if ($exists) {
    $stmt = $con->prepare("UPDATE patient_statistics SET 
        total_treatment_cost = ?, 
        amount_paid = ?, 
        remaining_amount = ?, 
        is_paid = ?, 
        updated_at = CURRENT_TIMESTAMP 
        WHERE patient_id = ?");
    $stmt->execute([$total_cost, $total_paid, $remaining, $is_paid, $patient_id]);
} else {
    $stmt = $con->prepare("INSERT INTO patient_statistics (
        patient_id, total_treatment_cost, amount_paid, remaining_amount, is_paid
    ) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$patient_id, $total_cost, $total_paid, $remaining, $is_paid]);
}

echo json_encode([
    "status" => "success",
    "total_treatment_cost" => $total_cost,
    "amount_paid" => $total_paid,
    "remaining_amount" => $remaining,
    "is_paid" => $is_paid
]);
