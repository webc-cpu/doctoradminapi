<?php
include ROOT_PATH . 'connect.php'; // مسار الاتصال

$id_visitor = filterRequest("id_visitor");

$stmt = $con->prepare("SELECT * FROM visitors WHERE id_visitor = ?");
$stmt->execute([$id_visitor]);

$data = $stmt->fetch(PDO::FETCH_ASSOC);

if ($data) {
    echo json_encode(["status" => "success", "data" => $data]);
} else {
    echo json_encode(["status" => "fail", "message" => "الزائر غير موجود"]);
}
