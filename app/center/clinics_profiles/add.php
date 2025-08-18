<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

include ROOT_PATH . 'connect.php';

// جلب البيانات المطلوبة
$user_id     = filterRequest("user_id");
$id_boss     = filterRequest("id_boss");
$clinic_name = filterRequest("clinic_name");
$address     = filterRequest("address");
$website     = filterRequest("website");
$phone       = filterRequest("phone");
$description = filterRequest("description");

// التعامل مع profile_type ليقبل نص أو رقم
$profile_type_raw = filterRequest("profile_type");
$profile_type = ($profile_type_raw === null) ? 0 : ((string)$profile_type_raw === "1" || (int)$profile_type_raw === 1 ? 1 : 0);

// استلام قيمة clinic_type من الطلب
$clinic_type_raw = filterRequest("clinic_type");

// تحديد clinic_type تلقائياً
if (($user_id && !$id_boss) && (!$clinic_type_raw || $clinic_type_raw === '')) {
    $clinic_type = "عيادة طبية";
} elseif (($id_boss && !$user_id) && (!$clinic_type_raw || $clinic_type_raw === '')) {
    $clinic_type = "مركز صحي";
} else {
    $clinic_type = $clinic_type_raw ?: "clinic";
}

// القيمة الافتراضية لحقل working_days (طوال أيام الأسبوع 24/24)
$default_working_days = "(طوال أيام الأسبوع 24/24)";

// استلام قيمة working_days من الطلب أو تعيين القيمة الافتراضية
$working_days = filterRequest("working_days") ?: $default_working_days;

// تحقق من الحقول الأساسية
if (!$clinic_name || !$address || !$phone) {
    echo json_encode([
        "status" => "fail",
        "message" => "يرجى التأكد من إدخال اسم العيادة، العنوان، ورقم الهاتف"
    ]);
    exit;
}

// ✅ تحقق من صلاحية اليوزر
if ($user_id) {
    $stmtUser = $con->prepare("SELECT boss_user FROM users WHERE user_id = ?");
    $stmtUser->execute([$user_id]);
    $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);

    // إذا عندو boss_user حقيقي (مو null أو 0 أو فاضي)، امنعو
    if (!empty($userData['boss_user']) && $userData['boss_user'] != 0) {
        echo json_encode([
            "status" => "fail",
            "message" => "لا يمكنك إنشاء ملف لأنك تابع للمركز"
        ]);
        exit;
    }

    // تحقق إذا عنده ملف سابق
    $stmtCheck = $con->prepare("SELECT id_clinic FROM clinics_profiles WHERE user_id = ?");
    $stmtCheck->execute([$user_id]);
    if ($stmtCheck->fetch()) {
        echo json_encode([
            "status" => "fail",
            "message" => "لديك ملف تعريفي موجود مسبقاً ولا يمكنك إضافة ملف جديد"
        ]);
        exit;
    }
}

// ✅ تحقق إذا البوس عنده ملف سابق
if ($id_boss) {
    $stmtCheckBoss = $con->prepare("SELECT id_clinic FROM clinics_profiles WHERE id_boss = ?");
    $stmtCheckBoss->execute([$id_boss]);
    if ($stmtCheckBoss->fetch()) {
        echo json_encode([
            "status" => "fail",
            "message" => "هناك ملف تعريفي موجود مسبقاً ولا يمكن إضافة ملف جديد"
        ]);
        exit;
    }
}

// الإدخال مع حقل working_days
$stmt = $con->prepare("INSERT INTO clinics_profiles 
    (user_id, id_boss, clinic_name, address, website, phone, description, profile_type, clinic_type, working_days)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$executed = $stmt->execute([
    $user_id ?: null,
    $id_boss ?: null,
    $clinic_name,
    $address,
    $website ?: null,
    $phone,
    $description ?: null,
    $profile_type,
    $clinic_type,
    $working_days
]);

if ($executed) {
    echo json_encode([
        "status" => "success",
        "message" => "تم إنشاء الملف التعريفي للعيادة بنجاح",
        "id_clinic" => $con->lastInsertId()
    ]);
} else {
    echo json_encode([
        "status" => "fail",
        "message" => "حدث خطأ أثناء إضافة الملف التعريفي"
    ]);
}
