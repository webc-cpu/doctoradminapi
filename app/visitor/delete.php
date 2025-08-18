<?php
// include "../../connect.php";
include ROOT_PATH . 'connect.php';

$id_visitor = filterRequest("id_visitor");

$stmt = $con->prepare("DELETE FROM visitors WHERE id_visitor = ?");
$stmt->execute([$id_visitor]);

if ($stmt->rowCount() > 0) {
    echo json_encode(["status" => "success", "message" => "تم حذف الزائر بنجاح"]);
} else {
    echo json_encode(["status" => "fail", "message" => "فشل في حذف الزائر أو الزائر غير موجود"]);
}
