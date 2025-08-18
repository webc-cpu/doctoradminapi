<?php
include ROOT_PATH . 'connect.php'; // ربط الاتصال

// استقبال المعرفات
$user_id      = filterRequest("user_id");
$treatment_id = filterRequest("treatment_id");
$patient_id   = filterRequest("patient_id");
$limit        = filterRequest("limit");
$offset       = filterRequest("offset");
$from_date    = filterRequest("from_date");
$to_date      = filterRequest("to_date");

// تأكد أن واحد منهم موجود على الأقل
if (empty($user_id) && empty($treatment_id) && empty($patient_id)) {
    echo json_encode(array("status" => "fail", "message" => "user_id أو treatment_id أو patient_id مطلوب"));
    exit;
}

// بناء شرط البحث ديناميكي حسب المعرفات الموجودة
$whereClauses = [];
$params = [];

if (!empty($user_id)) {
    $whereClauses[] = "tl.`user_id` = ?";
    $params[] = $user_id;
}
if (!empty($treatment_id)) {
    $whereClauses[] = "tl.`treatment_id` = ?";
    $params[] = $treatment_id;
}
if (!empty($patient_id)) {
    $whereClauses[] = "tl.`patient_id` = ?";
    $params[] = $patient_id;
}
if (!empty($from_date)) {
    $whereClauses[] = "tl.`log_date` >= ?";
    $params[] = $from_date;
}
if (!empty($to_date)) {
    $whereClauses[] = "tl.`log_date` <= ?";
    $params[] = $to_date;
}

// دمج شروط WHERE باستخدام AND
$whereSQL = !empty($whereClauses) ? implode(" AND ", $whereClauses) : "1";

$sql = "SELECT tl.*, u.user_name as user_name 
        FROM treatment_logs tl
        LEFT JOIN users u ON tl.user_id = u.user_id
        WHERE $whereSQL";

// إضافة LIMIT و OFFSET إذا تم تمريرهم
if (!empty($limit)) {
    $sql .= " LIMIT " . intval($limit);
    if (!empty($offset)) {
        $sql .= " OFFSET " . intval($offset);
    }
}

$stmt = $con->prepare($sql);
$stmt->execute($params);

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
$count = $stmt->rowCount();

if ($count > 0) {
    // تحويل الوقت إلى نظام 12 ساعة لكل سجل
    foreach ($data as &$row) {
        // إذا كان الحقل يحتوي على تاريخ ووقت
        if (isset($row['log_date'])) {
            $timestamp = strtotime($row['log_date']);
            if ($timestamp !== false) {
                $row['log_date'] = date('Y-m-d h:i:s A', $timestamp); // 12 ساعة مع AM/PM
            }
        }
        
        // التأكد من وجود user_name
        if (!isset($row['user_name'])) {
            $row['user_name'] = 'غير معروف';
        }
    }
    
    echo json_encode(array("status" => "success", "data" => $data));
} else {
    echo json_encode(array("status" => "fail", "message" => "لا توجد سجلات لوجز"));
}