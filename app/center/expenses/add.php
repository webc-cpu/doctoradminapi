<?php
include ROOT_PATH . 'connect.php'; // ربط الاتصال
header("Content-Type: application/json");

// استقبال البيانات
$expense_name    = filterRequest("expense_name");
$expense_type    = filterRequest("expense_type");
$amount          = filterRequest("amount");
$user_id         = filterRequest("user_id");
$id_boss         = filterRequest("id_boss");
$secretary_id    = filterRequest("secretary_id");
$center_expense  = filterRequest("center_expense");
$note            = filterRequest("note") ?: "لا يوجد";
$expense_date    = date('Y-m-d H:i:s');

// ✅ التحقق من الحقول الأساسية المطلوبة
if (empty($expense_name) || empty($expense_type) || empty($amount)) {
    echo json_encode(["status" => "fail", "message" => "الحقول الأساسية مفقودة"]);
    exit;
}

// ✅ التحقق من وجود على الأقل أحد المعرّفات الثلاثة
if (empty($user_id) && empty($id_boss) && empty($secretary_id)) {
    echo json_encode(["status" => "fail", "message" => "يجب تحديد أحد المعرفات: user_id أو id_boss أو secretary_id"]);
    exit;
}

// تنفيذ الإدخال
$sql = "INSERT INTO expenses (
            expense_name, expense_type, amount, user_id, note, 
            expense_date, center_expense, id_boss, secretary_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $con->prepare($sql);
$result = $stmt->execute([
    $expense_name,
    $expense_type,
    $amount,
    $user_id,
    $note,
    $expense_date,
    $center_expense,
    $id_boss,
    $secretary_id
]);

if ($result) {
    echo json_encode(["status" => "success", "message" => "تمت إضافة المصروف بنجاح"]);
} else {
    echo json_encode(["status" => "fail", "message" => "فشل في إضافة المصروف"]);
}
