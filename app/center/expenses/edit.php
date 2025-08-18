<?php
include ROOT_PATH . 'connect.php'; // الاتصال + دوال مساعدة

// استقبال المعرف
$expense_id = filterRequest("expense_id");

if (empty($expense_id)) {
    echo json_encode(["status" => "fail", "message" => "معرف المصروف مطلوب للتعديل"]);
    exit;
}

// جلب البيانات الحالية من قاعدة البيانات
$stmt = $con->prepare("SELECT * FROM expenses WHERE expense_id = ?");
$stmt->execute([$expense_id]);
$current = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$current) {
    echo json_encode(["status" => "fail", "message" => "❌ المصروف غير موجود"]);
    exit;
}

// إعداد البيانات الجديدة بناءً على ما تم إرساله فقط
$fields = [
    "expense_name"   => filterRequest("expense_name") ?? $current["expense_name"],
    "expense_type"   => filterRequest("expense_type") ?? $current["expense_type"],
    "amount"         => filterRequest("amount") ?? $current["amount"],
    "user_id"        => filterRequest("user_id") ?? $current["user_id"],
    "note"           => filterRequest("note") ?? $current["note"],
    "center_expense" => filterRequest("center_expense") ?? $current["center_expense"],
];

// التحقق من صحة القيمة الرقمية للمبلغ
if (!is_numeric($fields["amount"])) {
    echo json_encode(["status" => "fail", "message" => "قيمة المبلغ غير صحيحة"]);
    exit;
}

// تجهيز أمر SQL الديناميكي
$sql = "UPDATE expenses SET ";
$params = [];
foreach ($fields as $column => $value) {
    $sql .= "$column = ?, ";
    $params[] = $value;
}
$sql = rtrim($sql, ", ") . " WHERE expense_id = ?";
$params[] = $expense_id;

// تنفيذ التعديل
$stmt = $con->prepare($sql);
$success = $stmt->execute($params);

// الرد حسب النتيجة
if ($success && $stmt->rowCount() > 0) {
    echo json_encode(["status" => "success", "message" => "✅ تم تعديل المصروف بنجاح"]);
} else {
    echo json_encode(["status" => "fail", "message" => "⚠️ لم يتم تعديل أي شيء أو البيانات كما هي"]);
}
