<?php
include ROOT_PATH . 'connect.php'; // ربط الاتصال

$id_boss = filterRequest("id_boss");

$stmt = $con->prepare("SELECT * FROM boss_settings WHERE id_boss = ?");
$stmt->execute([$id_boss]);

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($stmt->rowCount() > 0) {
    echo json_encode([
        "status" => "success",
        "data" => $data
    ]);
} else {
    echo json_encode([
        "status" => "failed",
        "message" => "لا يوجد إعدادات لهذا المدير"
    ]);
}
