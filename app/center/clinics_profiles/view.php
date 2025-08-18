<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

include ROOT_PATH . 'connect.php';

$user_id       = filterRequest("user_id");
$boss_id       = filterRequest("id_boss");
$secretary_id  = filterRequest("secretary_id");

try {
    if ($user_id) {
        $stmt = $con->prepare("SELECT * FROM clinics_profiles WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $profiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($profiles)) {
            // منجيب boss_user من جدول users
            $stmtUser = $con->prepare("SELECT boss_user FROM users WHERE user_id = ?");
            $stmtUser->execute([$user_id]);
            $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);

            if ($userData && $userData['boss_user']) {
                $stmt = $con->prepare("SELECT * FROM clinics_profiles WHERE id_boss = ?");
                $stmt->execute([$userData['boss_user']]);
                $profiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (empty($profiles)) {
                    echo json_encode(["status" => "fail", "message" => "لم يتم إنشاء ملف للمركز"]);
                    exit;
                }

            } else {
                echo json_encode(["status" => "fail", "message" => "لم يتم إنشاء ملف للعيادة"]);
                exit;
            }
        }

    } elseif ($boss_id) {
        $stmt = $con->prepare("SELECT * FROM clinics_profiles WHERE id_boss = ?");
        $stmt->execute([$boss_id]);
        $profiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($profiles)) {
            echo json_encode(["status" => "fail", "message" => "لم يتم إنشاء ملف للمركز"]);
            exit;
        }

    } elseif ($secretary_id) {
        $stmtSec = $con->prepare("SELECT boss_secretary, user_secretary FROM secretaries WHERE secretary_id = ?");
        $stmtSec->execute([$secretary_id]);
        $secData = $stmtSec->fetch(PDO::FETCH_ASSOC);

        if ($secData) {
            if ($secData['boss_secretary']) {
                $stmt = $con->prepare("SELECT * FROM clinics_profiles WHERE id_boss = ?");
                $stmt->execute([$secData['boss_secretary']]);
                $profiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (empty($profiles)) {
                    echo json_encode(["status" => "fail", "message" => "لم يتم إنشاء ملف للمركز"]);
                    exit;
                }

            } elseif ($secData['user_secretary']) {
                $stmt = $con->prepare("SELECT * FROM clinics_profiles WHERE user_id = ?");
                $stmt->execute([$secData['user_secretary']]);
                $profiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (empty($profiles)) {
                    echo json_encode(["status" => "fail", "message" => "لم يتم إنشاء ملف للعيادة"]);
                    exit;
                }

            } else {
                echo json_encode(["status" => "fail", "message" => "لا يوجد جهة تابعة للسكرتيرة"]);
                exit;
            }

        } else {
            echo json_encode(["status" => "fail", "message" => "السكرتيرة غير موجودة"]);
            exit;
        }

    } else {
        echo json_encode(["status" => "fail", "message" => "يرجى تحديد هوية المستخدم"]);
        exit;
    }

    echo json_encode([
        "status" => "success",
        "data" => $profiles
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "status" => "fail",
        "message" => "حدث خطأ في السيرفر: " . $e->getMessage()
    ]);
}
