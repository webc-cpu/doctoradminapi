<?php
// include "../../connect.php";

include ROOT_PATH . 'connect.php';

$id_visitor       = filterRequest("id_visitor");
$visitor_name     = filterRequest("visitor_name");
$visitor_email    = filterRequest("visitor_email");
$visitor_phone    = filterRequest("visitor_phone");

$stmt = $con->prepare("UPDATE visitors SET 
    visitor_name = ?, 
    visitor_email = ?, 
    visitor_phone = ?
    WHERE id_visitor = ?
");

$stmt->execute([$visitor_name, $visitor_email, $visitor_phone, $id_visitor]);

if ($stmt->rowCount() > 0) {
    echo json_encode(["status" => "success", "message" => "تم تحديث بيانات الزائر بنجاح"]);
} else {
    echo json_encode(["status" => "fail", "message" => "لم يتم تعديل أي بيانات أو الزائر غير موجود"]);
}
