<?php
include ROOT_PATH . 'connect.php'; // ربط الاتصال
// يحتوي على الاتصال + دوال المساعدة مثل filterRequest و updateBossStatus

$id_boss = filterRequest("id_boss");

if (!empty($id_boss)) {
    // جلب المصاريف المرتبطة بالسكرتيرة فقط
    $stmt = $con->prepare("SELECT * FROM expenses WHERE id_boss = ?");
    $stmt->execute([$id_boss]);
} else {
    // جلب كل المصاريف
    $stmt = $con->prepare("SELECT * FROM expenses");
    $stmt->execute();
}

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
$count = $stmt->rowCount();

if ($count > 0) {
    echo json_encode(array("status" => "success", "data" => $data));
} else {
    echo json_encode(array("status" => "fail", "message" => "لا توجد بيانات مصاريف"));
}
