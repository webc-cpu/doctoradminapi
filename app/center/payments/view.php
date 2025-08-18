<?php

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

include_once ROOT_PATH . 'connect.php';
include_once ROOT_PATH . 'functions.php'; // من أجل filterRequest

$patient_id = filterRequest("patient_id");

if (!$patient_id) {
    echo json_encode([
        "status" => "fail",
        "message" => "patient_id مفقود"
    ]);
    exit;
}

// استعلام متقدم لجلب الدفعات + اسم ونوع المسجل مترجم
$sql = "
    SELECT
        p.*,
        COALESCE(b.boss_name, u.user_name, s.secretary_name) AS registered_by_name,
        CASE
            WHEN b.id_boss IS NOT NULL THEN 'المدير'
            WHEN u.user_id IS NOT NULL THEN 'الطبيب'
            WHEN s.secretary_id IS NOT NULL THEN 'السكرتيرا'
            ELSE 'غير معروف'
        END AS registered_by_type
    FROM payments p
    LEFT JOIN boss b ON b.id_boss = p.registered_by_id
    LEFT JOIN users u ON u.user_id = p.registered_by_id
    LEFT JOIN secretaries s ON s.secretary_id = p.registered_by_id
    WHERE p.patient_id = ?
    ORDER BY p.payment_date DESC
";

$stmt = $con->prepare($sql);
$stmt->execute([$patient_id]);

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($data) > 0) {
    echo json_encode([
        "status" => "success",
        "data" => $data
    ]);
} else {
    echo json_encode([
        "status" => "fail",
        "message" => "لا توجد دفعات لهذا المريض"
    ]);
}












// header("Access-Control-Allow-Origin: *");
// header("Content-Type: application/json");
// header("Access-Control-Allow-Headers: Content-Type, Authorization");
// header("Access-Control-Allow-Methods: POST");

// include_once "connect.php";
// include_once "functions.php";

// $boss_id      = filterRequest("boss_id");
// $user_id      = filterRequest("user_id");
// $secretary_id = filterRequest("secretary_id");

// try {
//     $sql = "
//     SELECT
//         pay.payment_id,
//         pay.patient_id,
//         p.patient_name,
//         pay.amount,
//         pay.payment_date,
//         pay.notes,

//         -- جلب اسم ومسجل الدفعة ونوعه
//         COALESCE(b.boss_name, u2.user_name, s.secretary_name) AS registered_by_name,
//         CASE
//             WHEN b.id_boss  IS NOT NULL THEN 'boss'
//             WHEN u2.user_id IS NOT NULL THEN 'user'
//             WHEN s.secretary_id IS NOT NULL THEN 'secretary'
//             ELSE 'unknown'
//         END AS registered_by_type

//     FROM payments pay
//     INNER JOIN patients p ON pay.patient_id = p.patient_id
//     INNER JOIN users u ON p.user_patient = u.user_id

//     -- LEFT JOIN على الجداول الثلاثة حسب registered_by_id
//     LEFT JOIN boss b ON b.id_boss  = pay.registered_by_id
//     LEFT JOIN users u2 ON u2.user_id = pay.registered_by_id
//     LEFT JOIN secretaries s ON s.secretary_id = pay.registered_by_id

//     WHERE 1 = 1
// ";


//     $params = [];

//     if ($secretary_id) {
//         // جلب بيانات السكرتيرة
//         $stmt = $con->prepare("SELECT boss_secretary, user_secretary FROM secretaries WHERE secretary_id = ?");
//         $stmt->execute([$secretary_id]);
//         $secretary = $stmt->fetch(PDO::FETCH_ASSOC);

//         if ($secretary) {
//             if ($secretary['boss_secretary']) {
//                 $sql .= " AND u.boss_user = ?";
//                 $params[] = $secretary['boss_secretary'];
//             } elseif ($secretary['user_secretary']) {
//                 $sql .= " AND u.user_id = ?";
//                 $params[] = $secretary['user_secretary'];
//             } else {
//                 echo json_encode([
//                     "status" => "error",
//                     "message" => "Secretary is not linked to any boss or user."
//                 ]);
//                 exit;
//             }
//         } else {
//             echo json_encode([
//                 "status" => "error",
//                 "message" => "Secretary not found."
//             ]);
//             exit;
//         }
//     } else {
//         // fallback إلى boss_id أو user_id إذا ما تم إرسال secretary_id
//         if (!$boss_id && !$user_id) {
//             echo json_encode([
//                 "status" => "error",
//                 "message" => "Either boss_id, user_id, or secretary_id is required."
//             ]);
//             exit;
//         }

//         if ($boss_id) {
//             $sql .= " AND u.boss_user = ?";
//             $params[] = $boss_id;
//         }

//         if ($user_id) {
//             $sql .= " AND u.user_id = ?";
//             $params[] = $user_id;
//         }
//     }

//     $sql .= " ORDER BY pay.payment_date DESC";

//     $stmt = $con->prepare($sql);
//     $stmt->execute($params);
//     $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

//     echo json_encode([
//         "status" => "success",
//         "data" => $payments
//     ]);

// } catch (PDOException $e) {
//     echo json_encode([
//         "status" => "error",
//         "message" => "Database error: " . $e->getMessage()
//     ]);
// }
