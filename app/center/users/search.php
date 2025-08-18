<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");

include ROOT_PATH . 'connect.php';

// استقبل المتغيرات
$id_boss = filterRequest("id_boss");
$search = "%" . filterRequest("search") . "%"; // نستخدم LIKE

try {
    // تحقق من وجود id_boss
    if (!$id_boss) {
        echo json_encode(["status" => "error", "message" => "رقم البوس مفقود"]);
        exit;
    }

    // استعلام البحث
    $stmt = $con->prepare("SELECT * FROM users WHERE boss_user = ? AND (user_name LIKE ? OR user_email LIKE ?)");
    $stmt->execute([$id_boss, $search, $search]);

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($users) {
        echo json_encode(["status" => "success", "users" => $users]);
    } else {
        echo json_encode(["status" => "empty", "message" => "ما في نتائج"]);
    }
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "خطأ: " . $e->getMessage()]);
}
