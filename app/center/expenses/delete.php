<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

include ROOT_PATH . 'connect.php'; // ربط الاتصال
// يحتوي على الاتصال + دوال المساعدة مثل filterRequest و updateBossStatus

$session_id = filterRequest("expense_id");

$stmt = $con->prepare("DELETE FROM expenses WHERE expense_id = ?");
$isDeleted = $stmt->execute(array($session_id));

if ($isDeleted) {
    echo json_encode(array("status" => "success"));
} else {
    echo json_encode(array("status" => "fail"));
}
?>