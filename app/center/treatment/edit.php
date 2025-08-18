<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

include ROOT_PATH . 'connect.php';
include_once ROOT_PATH . 'functions.php';

$treatment_id = filterRequest("treatment_id");
if (!$treatment_id) {
    echo json_encode(["status" => "fail", "message" => "معرّف العلاج مطلوب"]);
    exit;
}

// جلب بيانات العلاج
$stmt = $con->prepare("SELECT treatment_name, patient_treatment, user_treatment, is_finished FROM treatment WHERE treatment_id = ?");
$stmt->execute([$treatment_id]);
$t = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$t) {
    echo json_encode(["status" => "fail", "message" => "العلاج غير موجود"]);
    exit;
}

$treatment_name    = $t['treatment_name'];
$user_treatment    = $t['user_treatment'];
$patient_treatment = $t['patient_treatment'];
$current_is_finished = $t['is_finished'];

$patient_id = filterRequest("patient_treatment") ?: $patient_treatment;

$stmt = $con->prepare("SELECT patient_name, patient_card FROM patients WHERE patient_id = ?");
$stmt->execute([$patient_id]);
$pd = $stmt->fetch(PDO::FETCH_ASSOC);
$patient_name = $pd['patient_name'] ?? 'اسم المريض غير معروف';
$patient_card = $pd['patient_card'] ?? 'بدون رقم بطاقة';

// الحقول الممكن تعديلها
$fieldsKeys = [
    "treatment_name", "treatment_card", "treatment_number",
    "treatment_date", "treatment_payment", "treatment_total",
    "treatment_note", "patient_treatment", "is_finished"
];

// القيم القديمة
$oldStmt = $con->prepare(
    "SELECT " . implode(", ", array_map(fn($c) => "`$c`", $fieldsKeys)) . " FROM treatment WHERE treatment_id = ?"
);
$oldStmt->execute([$treatment_id]);
$oldValues = $oldStmt->fetch(PDO::FETCH_ASSOC);

// القيم الجديدة
$fields = [
    "treatment_name"   => filterRequest("treatment_name"),
    "treatment_card"   => filterRequest("treatment_card"),
    "treatment_number" => filterRequest("treatment_number"),
    "treatment_date"   => filterRequest("treatment_date"),
    "treatment_payment"=> filterRequest("treatment_payment"),
    "treatment_total"  => filterRequest("treatment_total"),
    "treatment_note"   => filterRequest("treatment_note"),
    "patient_treatment"=> filterRequest("patient_treatment"),
    "is_finished"      => filterRequest("is_finished")
];

if ($fields['is_finished'] !== null) {
    $fields['is_finished'] = ($fields['is_finished'] == 1) ? 1 : 0;
} else {
    unset($fields['is_finished']);
}

// بناء update
$setClause = [];
$params = [];
foreach ($fields as $col => $val) {
    if (
        $col === 'treatment_payment'
            ? !is_null($val) && ($val === 0 || $val === "0" || $val !== "")
            : $val !== null && $val !== ""
    ) {
        $setClause[] = "`$col` = ?";
        $params[]    = $val;
    }
}

if (!empty($fields['patient_treatment'])) {
    $patient_id = $fields['patient_treatment'];
}

if (count($setClause) > 0) {
    $params[] = $treatment_id;
    $sql = "UPDATE `treatment` SET " . implode(", ", $setClause) . " WHERE `treatment_id` = ?";
    $con->prepare($sql)->execute($params);
    updatePatientStatistics($con, $patient_id);
}

// تسجيل لوج التعديلات
function logAction($con, $treatment_id, $user_id, $patient_id, $action, $details, $name, $card) {
    $fullDetails = $details . " للمريض: $name - البطاقة: $card";
    $con->prepare("INSERT INTO treatment_logs (treatment_id, user_id, patient_id, action, details) VALUES (?, ?, ?, ?, ?)")
        ->execute([$treatment_id, $user_id, $patient_id, $action, $fullDetails]);
}

if (count($setClause) > 0) {
    $translated = [
        "treatment_name" => "اسم العلاج", "treatment_card" => "بطاقة العلاج",
        "treatment_number" => "رقم العلاج", "treatment_date" => "تاريخ العلاج",
        "treatment_payment" => "الدفعة", "treatment_total" => "إجمالي الواجب",
        "treatment_note" => "الملاحظة", "patient_treatment" => "المريض", "is_finished" => "حالة الجلسة"
    ];
    $changes = [];
    foreach ($fields as $col => $newVal) {
        if ($newVal !== null && ($newVal !== "" || $newVal === "0" || $newVal === 0) && isset($oldValues[$col]) && $oldValues[$col] != $newVal) {
            $label = $translated[$col] ?? $col;
            $newVal = ($col === 'is_finished') ? (($newVal == 1) ? "منتهية" : "غير منتهية") : $newVal;
            $changes[] = "$label: $newVal";
        }
    }
    if ($changes) {
        $base = "تم تعديل " . implode("، ", $changes);
        logAction($con, $treatment_id, $user_treatment, $patient_id, "تعديل", $base, $patient_name, $patient_card);
    }
}

updatePatientTheRest($con, $patient_treatment);
// تحديث الإحصائيات
$updateResult = updatePatientStatistics($con, $patient_treatment);
if ($updateResult !== true) {
    error_log("Failed to update patient statistics: " . $updateResult);
}

echo json_encode(["status" => "success", "message" => "تم تعديل بيانات العلاج بنجاح"]);
