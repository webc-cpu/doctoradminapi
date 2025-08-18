<?php
include ROOT_PATH . 'connect.php';

$id_boss = filterRequest("id_boss");

// 1. جلب كل اليوزرات المرتبطين بالبوس
$stmt = $con->prepare("SELECT user_id FROM users WHERE boss_user = ?");
$stmt->execute([$id_boss]);
$user_ids = $stmt->fetchAll(PDO::FETCH_COLUMN); // بترجع مصفوفة تحتوي على أعمدة user_id فقط

$patients = [];

if (!empty($user_ids)) {
    // 2. بناء placeholders للاستعلام الآمن
    $placeholders = implode(',', array_fill(0, count($user_ids), '?'));

    // 3. جلب المرضى التابعين لهاليوزرات
    $stmt = $con->prepare("SELECT * FROM patients WHERE user_patient IN ($placeholders)");
    $stmt->execute($user_ids);
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// طباعة النتيجة كـ JSON
echo json_encode([
    "status" => "success",
    "count" => count($patients),
    "data" => $patients
]);
exit;
