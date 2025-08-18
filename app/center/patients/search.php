<?php
include ROOT_PATH . 'connect.php'; // ربط الاتصال
// يحتوي على الاتصال + دوال المساعدة مثل filterRequest و updateBossStatus

$user_id = filterRequest("user_id");
$search_query = filterRequest("search_query"); // استلام الاسم المراد البحث عنه

// إذا كان هناك قيمة للبحث، يتم تعديل الاستعلام ليشمل البحث عن الاسم
if (!empty($search_query)) {
    $stmt = $con->prepare("SELECT * FROM patients WHERE `user_patient` = ? AND `patient_name` LIKE ?");
    $stmt->execute(array($user_id, "%$search_query%"));
} else {
    $stmt = $con->prepare("SELECT * FROM patients WHERE `user_patient` = ?");
    $stmt->execute(array($user_id));
}

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
$count = $stmt->rowCount();

if ($count > 0) {
    echo json_encode(array("status" => "success", "data" => $data));
} else {
    echo json_encode(array("status" => "fail"));
}
?>
