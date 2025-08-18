<?php
include ROOT_PATH . 'connect.php'; // ربط الاتصال
// يحتوي على الاتصال + دوال المساعدة مثل filterRequest و updateBossStatus

// الحقول المطلوبة
$fields = [
    "patient_name"   => filterRequest("patient_name"),
    "Phone_Number"   => filterRequest("Phone_Number"),
    "Drug_Allergies" => filterRequest("Drug_Allergies"),
    "Address"        => filterRequest("Address"),
    "Age"            => filterRequest("Age"),
    "Gender"         => filterRequest("Gender"),
    "Pregnant"       => filterRequest("Pregnant"),
    "Smoker"         => filterRequest("Smoker"),
    "patient_card"   => filterRequest("patient_card"),
    "patient_date"   => filterRequest("patient_date"),
    "user_patient"   => filterRequest("user_patient"),
];

// احذف أي قيمة فاضية أو null
$filteredFields = array_filter($fields, function ($value) {
    return $value !== null && $value !== "";
});

if (count($filteredFields) < 2 || !isset($filteredFields["user_patient"])) {
    echo json_encode(["status" => "fail", "message" => "بيانات ناقصة"]);
    exit;
}

// بناء أسماء الأعمدة والقيم ديناميكياً
$columns = implode(", ", array_keys($filteredFields));
$placeholders = implode(", ", array_fill(0, count($filteredFields), "?"));
$values = array_values($filteredFields);

$sql = "INSERT INTO patients ($columns) VALUES ($placeholders)";
$stmt = $con->prepare($sql);
$stmt->execute($values);

if ($stmt->rowCount() > 0) {
    $patient_id = $con->lastInsertId();
    updatePatientTheRest($con, $patient_id);
    echo json_encode([
        "status" => "success",
        "patient_id" => $patient_id
    ]);
} else {
    echo json_encode(["status" => "fail"]);
}
