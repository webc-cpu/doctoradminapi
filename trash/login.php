<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

include ROOT_PATH . 'connect.php';

// قراءة JSON إذا موجود
$input = json_decode(file_get_contents("php://input"), true);

// جلب البيانات من POST أو GET أو JSON
$boss_email = $_POST["boss_email"] ?? $_GET["boss_email"] ?? $input["boss_email"] ?? null;
$boss_password = $_POST["boss_password"] ?? $_GET["boss_password"] ?? $input["boss_password"] ?? null;

$stmt = $con->prepare("SELECT * FROM boss WHERE boss_password = ? AND boss_email = ?");
$stmt->execute([$boss_password, $boss_email]);

$data = $stmt->fetch(PDO::FETCH_ASSOC);
$count = $stmt->rowCount();

if ($count > 0) {
    $status = $data['is_active'];

    if ($status === "مفعل" || $status === "مفعل كضيف") {
        // تسجيل دخول ناجح
        echo json_encode([
            "status" => "success",
            "data" => $data
        ]);
    } else {
        // مرفوض - يعرض فقط حالة الاشتراك
        echo json_encode([
            "status" => "denied",
            "is_active" => $status,
            "message" => "لا يمكنك تسجيل الدخول، : $status"
        ]);
    }
} else {
    echo json_encode(["status" => "fail", "message" => "فشل هناك حطأفي كلمة المرور أو الايميل"]);
}
