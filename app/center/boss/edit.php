<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
include ROOT_PATH . 'connect.php'; // ربط الاتصال
// يحتوي على الاتصال + دوال المساعدة مثل filterRequest و updateBossStatus

$id_boss         = filterRequest("id_boss");
$boss_name       = filterRequest("boss_name");
$boss_start_date = filterRequest("boss_start_date");
$boss_end_date   = filterRequest("boss_end_date");
$max_users       = filterRequest("max_users");
$boss_email      = filterRequest("boss_email");
$boss_password   = filterRequest("boss_password");

$fields = [];
$params = [];

// إعداد الحقول للتعديل
if (!empty($boss_name)) {
    $fields[] = "`boss_name` = ?";
    $params[] = $boss_name;
}
if (!empty($boss_start_date)) {
    $fields[] = "`boss_start_date` = ?";
    $params[] = $boss_start_date;
}
if (!empty($boss_end_date)) {
    $fields[] = "`boss_end_date` = ?";
    $params[] = $boss_end_date;
}
if (!empty($max_users)) {
    $fields[] = "`max_users` = ?";
    $params[] = $max_users;
}
if (!empty($boss_email)) {
    $fields[] = "`boss_email` = ?";
    $params[] = $boss_email;
}
if (!empty($boss_password)) {
    // تشفير كلمة المرور إذا تم إرسالها
    $fields[] = "`boss_password` = ?";
    $params[] = password_hash($boss_password, PASSWORD_DEFAULT);
}

if (count($fields) > 0) {
    $sql = "UPDATE `boss` SET " . implode(", ", $fields) . " WHERE `id_boss` = ?";
    $params[] = $id_boss;

    $stmt = $con->prepare($sql);
    $stmt->execute($params);

    $count = $stmt->rowCount();

    if ($count > 0) {
        // استدعاء الدالة لتحديث الحالة وعدد اليوزر
        updateBossStatus($con, $id_boss);

        echo json_encode(array("status" => "success"));
    } else {
        echo json_encode(array("status" => "fail", "message" => "No rows affected"));
    }
} else {
    echo json_encode(array("status" => "no_changes", "message" => "No fields sent for update"));
}
