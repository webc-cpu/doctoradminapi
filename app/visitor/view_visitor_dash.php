<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

include ROOT_PATH . 'connect.php';

$user_id  = filterRequest("user_id");
$boss_id  = filterRequest("id_boss");

try {
    if ($user_id) {
        // جلب الملف المرتبط باليوزر أو بالبوس التابع إلو
        $stmt = $con->prepare("SELECT * FROM clinics_profiles WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $profiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($profiles)) {
            $stmtUser = $con->prepare("SELECT boss_user FROM users WHERE user_id = ?");
            $stmtUser->execute([$user_id]);
            $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);

            if ($userData && $userData['boss_user']) {
                $stmt = $con->prepare("SELECT * FROM clinics_profiles WHERE id_boss = ?");
                $stmt->execute([$userData['boss_user']]);
                $profiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }

    } elseif ($boss_id) {
        // جلب الملف المرتبط بالبوس
        $stmt = $con->prepare("SELECT * FROM clinics_profiles WHERE id_boss = ?");
        $stmt->execute([$boss_id]);
        $profiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } else {
        // إذا ما انبعت شي → رجّع كل الملفات
        $stmt = $con->prepare("SELECT * FROM clinics_profiles");
        $stmt->execute();
        $profiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    if (!empty($profiles)) {
        echo json_encode(["status" => "success", "data" => $profiles]);
    } else {
        echo json_encode(["status" => "fail", "message" => "لا يوجد ملفات متاحة"]);
    }

} catch (PDOException $e) {
    echo json_encode([
        "status" => "fail",
        "message" => "حدث خطأ في السيرفر: " . $e->getMessage()
    ]);
}
