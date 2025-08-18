<?php
include ROOT_PATH . 'connect.php'; // ربط الاتصال
// يحتوي على الاتصال + دوال المساعدة مثل filterRequest و updateBossStatus

$secretary_name     = filterRequest("secretary_name");
$search_query  = filterRequest("search_query"); // استلام الاسم المراد البحث عنه

// إذا كان هناك قيمة للبحث، يتم تعديل الاستعلام ليشمل البحث عن الاسم
if (!empty($search_query)) {
    $stmt = $con->prepare("SELECT * FROM secretaries WHERE `secretary_name` = ? AND `secretary_name` LIKE ?");
    $stmt->execute(array($secretary_name, "%$search_query%"));
} else {
    $stmt = $con->prepare("SELECT * FROM secretaries WHERE `secretary_name` = ?");
    $stmt->execute(array($secretary_name));
}

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
$count = $stmt->rowCount();

if ($count > 0) {
    echo json_encode(array("status" => "success", "data" => $data));
} else {
    echo json_encode(array("status" => "fail"));
}

