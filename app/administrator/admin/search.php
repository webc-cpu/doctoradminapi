<?php
include ROOT_PATH . 'connect.php'; // ربط الاتصال
// يحتوي على الاتصال + دوال المساعدة مثل filterRequest و updateBossStatus

$search_query  = filterRequest("search_query"); // استلام الاسم المراد البحث عنه

if (!empty($search_query)) {
    // البحث عن الاسم ضمن العمود boss_name
    $stmt = $con->prepare("SELECT * FROM boss WHERE `boss_name` LIKE ?");
    $stmt->execute(array("%$search_query%"));
} else {
    // إذا ما في بحث، جيب كل البوسات
    $stmt = $con->prepare("SELECT * FROM boss");
    $stmt->execute();
}

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
$count = $stmt->rowCount();

if ($count > 0) {
    echo json_encode(array("status" => "success", "data" => $data));
} else {
    echo json_encode(array("status" => "fail"));
}
