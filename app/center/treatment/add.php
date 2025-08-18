<?php


header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

include ROOT_PATH . 'connect.php';

// استقبال البيانات
$treatment_name         = filterRequest("treatment_name");
$treatment_card         = filterRequest("treatment_card") ?? "";
$treatment_number       = filterRequest("treatment_number") ?? "";
$treatment_date         = filterRequest("treatment_date");
$treatment_payment      = filterRequest("treatment_payment") ?? 0;
$treatment_note         = filterRequest("treatment_note") ?? "";
$session_id             = filterRequest("session_id");
$treatment_price_client = filterRequest("treatment_price");
$is_finished            = (filterRequest("is_finished") == 1) ? 1 : 0;

// جلب patient_id و user_id من الجلسة
$stmt = $con->prepare("SELECT patient_id, user_id FROM sessions WHERE session_id = ?");
$stmt->execute([$session_id]);
$sessionData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sessionData) {
    echo json_encode(["status" => "fail", "message" => "الجلسة غير موجودة"]);
    exit;
}

$patient_treatment = $sessionData['patient_id'];
$user_treatment    = $sessionData['user_id'];

// جلب boss_id
$stmt = $con->prepare("SELECT boss_user FROM users WHERE user_id = ?");
$stmt->execute([$user_treatment]);
$boss_id = $stmt->fetchColumn();

// تحديد السعر
if ($boss_id) {
    $stmt = $con->prepare("SELECT treatment_price FROM treatment_settings WHERE treatment_name = ? AND id_boss = ?");
    $stmt->execute([$treatment_name, $boss_id]);
    $treatment_total = $stmt->fetchColumn();

    if ($treatment_total === false && $treatment_total !== "0") {
        echo json_encode(["status" => "fail", "message" => "العلاج غير موجود ضمن إعدادات البوس"]);
        exit;
    }
} else {
    if ($treatment_price_client === null || $treatment_price_client === "") {
        echo json_encode(["status" => "fail", "message" => "يرجى إرسال سعر العلاج لأن المستخدم غير مرتبط ببوس"]);
        exit;
    }
    $treatment_total = $treatment_price_client;
}

if (!$treatment_date) $treatment_date = date("Y-m-d");

// إدخال العلاج
$stmt = $con->prepare("
    INSERT INTO `treatment` (
        `treatment_name`, `treatment_card`, `treatment_number`, `treatment_date`,
        `treatment_payment`, `treatment_total`, `treatment_note`,
        `user_treatment`, `patient_treatment`, `session_id`, `is_finished`
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$isInserted = $stmt->execute([
    $treatment_name, $treatment_card, $treatment_number, $treatment_date,
    $treatment_payment, $treatment_total, $treatment_note,
    $user_treatment, $patient_treatment, $session_id, $is_finished
]);

if (!$isInserted) {
    echo json_encode(["status" => "fail", "message" => "فشل في إضافة العلاج"]);
    exit;
}

$treatment_id = $con->lastInsertId();

// جلب اسم المريض
$stmt = $con->prepare("SELECT patient_name, patient_card FROM patients WHERE patient_id = ?");
$stmt->execute([$patient_treatment]);
$patientData  = $stmt->fetch(PDO::FETCH_ASSOC);
$patient_name = $patientData['patient_name'] ?? "";
$patient_card = $patientData['patient_card'] ?? "";

// تسجيل لوج إضافة العلاج
$details  = "تم إضافة علاج جديد: $treatment_name";
if ($patient_name) {
    $details .= " للمريض: $patient_name";
    if ($patient_card) {
        $details .= " - رقم البطاقة: $patient_card";
    }
}

$stmt_log = $con->prepare("
    INSERT INTO treatment_logs (treatment_id, patient_id, user_id, action, details, log_date)
    VALUES (?, ?, ?, 'اضافة', ?, NOW())
");
$stmt_log->execute([$treatment_id, $patient_treatment, $user_treatment, $details]);

// تحديث الإحصائيات
$updateResult = updatePatientStatistics($con, $patient_treatment);
if ($updateResult !== true) {
    error_log("Failed to update patient statistics: " . $updateResult);
}

echo json_encode([
    "status" => "success",
    "treatment_id" => $treatment_id,
    "patient_id" => $patient_treatment,
    "user_id" => $user_treatment,
    "patient_name" => $patient_name,
    "patient_card" => $patient_card
]);







// header("Access-Control-Allow-Origin: *");
// header("Access-Control-Allow-Headers: Content-Type, Authorization");
// header("Access-Control-Allow-Methods: POST, OPTIONS");

// include ROOT_PATH . 'connect.php';

// // استقبال البيانات
// $treatment_name         = filterRequest("treatment_name");
// $treatment_card         = filterRequest("treatment_card") ?? "";
// $treatment_number       = filterRequest("treatment_number") ?? "";
// $treatment_date         = filterRequest("treatment_date");
// $treatment_payment      = filterRequest("treatment_payment") ?? 0;
// $treatment_note         = filterRequest("treatment_note") ?? "";
// $session_id             = filterRequest("session_id");
// $treatment_price_client = filterRequest("treatment_price"); // السعر في حال المستخدم ما مرتبط ببوس
// $is_finished            = filterRequest("is_finished");
// $is_finished            = ($is_finished == 1) ? 1 : 0;

// // جلب patient_id و user_id من جدول الجلسات
// $stmt = $con->prepare("SELECT patient_id, user_id FROM sessions WHERE session_id = ?");
// $stmt->execute([$session_id]);
// $sessionData = $stmt->fetch(PDO::FETCH_ASSOC);

// if (!$sessionData) {
//     echo json_encode(["status" => "fail", "message" => "الجلسة غير موجودة"]);
//     exit;
// }

// $patient_treatment = $sessionData['patient_id'];
// $user_treatment    = $sessionData['user_id'];

// // جلب boss_user للمستخدم
// $stmt = $con->prepare("SELECT boss_user FROM users WHERE user_id = ?");
// $stmt->execute([$user_treatment]);
// $boss_id = $stmt->fetchColumn();

// // تحديد السعر
// if ($boss_id) {
//     $stmt = $con->prepare("SELECT treatment_price FROM treatment_settings WHERE treatment_name = ? AND id_boss = ?");
//     $stmt->execute([$treatment_name, $boss_id]);
//     $treatment_total = $stmt->fetchColumn();

//     if ($treatment_total === false && $treatment_total !== "0") {
//         echo json_encode(["status" => "fail", "message" => "العلاج غير موجود ضمن إعدادات البوس"]);
//         exit;
//     }
// } else {
//     if ($treatment_price_client === null || $treatment_price_client === "") {
//         echo json_encode(["status" => "fail", "message" => "يرجى إرسال سعر العلاج لأن المستخدم غير مرتبط ببوس"]);
//         exit;
//     }
//     $treatment_total = $treatment_price_client;
// }

// // تاريخ العلاج
// if (!$treatment_date || empty($treatment_date)) {
//     $treatment_date = date("Y-m-d");
// }

// // إدخال بيانات العلاج
// $stmt = $con->prepare("
//     INSERT INTO `treatment` (
//         `treatment_name`, `treatment_card`, `treatment_number`, `treatment_date`,
//         `treatment_payment`, `treatment_total`, `treatment_note`,
//         `user_treatment`, `patient_treatment`, `session_id`, `is_finished`
//     ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
// ");

// $isInserted = $stmt->execute([
//     $treatment_name,
//     $treatment_card,
//     $treatment_number,
//     $treatment_date,
//     $treatment_payment,
//     $treatment_total,
//     $treatment_note,
//     $user_treatment,
//     $patient_treatment,
//     $session_id,
//     $is_finished
// ]);

// if (!$isInserted) {
//     echo json_encode(["status" => "fail", "message" => "فشل في إضافة العلاج"]);
//     exit;
// }

// $treatment_id = $con->lastInsertId();

// // جلب اسم المريض ورقم البطاقة
// $stmt = $con->prepare("SELECT patient_name, patient_card FROM patients WHERE patient_id = ?");
// $stmt->execute([$patient_treatment]);
// $patientData = $stmt->fetch(PDO::FETCH_ASSOC);

// $patient_name = $patientData['patient_name'] ?? "";
// $patient_card = $patientData['patient_card'] ?? "";

// // تسجيل اللوج
// $user_id  = $user_treatment;
// $details  = "تم إضافة علاج جديد: $treatment_name";
// if ($patient_name) {
//     $details .= " للمريض: $patient_name";
//     if ($patient_card) {
//         $details .= " - رقم البطاقة: $patient_card";
//     }
// }
// $action   = "اضافة";
// $stmt_log = $con->prepare("
//     INSERT INTO treatment_logs (treatment_id, patient_id, user_id, action, details, log_date)
//     VALUES (?, ?, ?, ?, ?, NOW())
// ");
// $stmt_log->execute([$treatment_id, $patient_treatment, $user_id, $action, $details]);

// // ** استدعاء دالة تحديث إحصائيات المريض بعد إضافة العلاج **
// $updateResult = updatePatientStatistics($con, $patient_treatment);
// if ($updateResult !== true) {
//     // يمكنك هنا تسجيل الخطأ أو التعامل معه حسب حاجتك
//     error_log("Failed to update patient statistics: " . $updateResult);
// }

// // رفع الصور إن وجدت
// if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
//     // مسار الرفع المطلوب داخل مجلد doctoradminapi/uploads/medicines/
//     $uploadDir = $_SERVER['DOCUMENT_ROOT'] . "/doctoradminapi/uploads/medicines/";


//     // ✅ تأكد أن المجلد موجود (إذا بدك تحذف هاد الشرط على الاستضافة المجانية، لازم تنشئ المجلد يدوياً)
//     if (!is_dir($uploadDir)) {
//         // لا تحاول إنشاء المجلد إذا ما عندك صلاحيات، أو احذف هالسطور نهائيًا إذا أنشأت المجلد يدويًا
//         // mkdir($uploadDir, 0777, true);
//     }

//     $allImagesUploaded = true;

//     foreach ($_FILES['images']['name'] as $index => $name) {
//         $error   = $_FILES['images']['error'][$index];
//         $tmpName = $_FILES['images']['tmp_name'][$index];

//         if ($error !== UPLOAD_ERR_OK) {
//             $allImagesUploaded = false;
//             break;
//         }

//         // ⚠️ حذف أي مسارات مرفقة بالاسم، وتوليد اسم جديد مع دعم الحروف العربية
// $encodedName = preg_replace('/[^A-Za-z0-9_\-\.]/u', '_', $name);
// $safeName = uniqid() . "_" . $encodedName;

//         $imagePath = $uploadDir . $safeName;

//         if (move_uploaded_file($tmpName, $imagePath)) {
//             $relativePath = "uploads/medicines/" . $safeName;

//             $stmt = $con->prepare("INSERT INTO `treatment_images` (`treatment_id`, `image_path`) VALUES (?, ?)");
//             $stmt->execute([$treatment_id, $relativePath]);

//             // لوج للصورة
//             $img_action = "إضافة صورة جديدة";
//             $img_detail = "تم رفع صورة جديدة للعلاج";
//             if ($patient_name) {
//                 $img_detail .= " للمريض: $patient_name";
//                 if ($patient_card) {
//                     $img_detail .= " - رقم البطاقة: $patient_card";
//                 }
//             }
//             $stmt_log = $con->prepare("
//                 INSERT INTO treatment_logs (treatment_id, patient_id, user_id, action, details, log_date)
//                 VALUES (?, ?, ?, ?, ?, NOW())
//             ");
//             $stmt_log->execute([$treatment_id, $patient_treatment, $user_id, $img_action, $img_detail]);

//         } else {
//             $allImagesUploaded = false;
//             break;
//         }
//     }

//     if ($allImagesUploaded) {
//         echo json_encode(["status" => "success", "treatment_id" => $treatment_id]);
//     } else {
//         echo json_encode(["status" => "fail", "message" => "فشل في رفع بعض الصور"]);
//     }
// } else {
//     echo json_encode(["status" => "success", "treatment_id" => $treatment_id]);
// }

