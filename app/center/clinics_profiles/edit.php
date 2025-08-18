<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

include ROOT_PATH . 'connect.php';

$id_clinic     = filterRequest("id_clinic");
$user_id       = filterRequest("user_id");
$id_boss       = filterRequest("id_boss");
$clinic_name   = filterRequest("clinic_name");
$address       = filterRequest("address");
$website       = filterRequest("website");
$phone         = filterRequest("phone");
$description   = filterRequest("description");
$profile_type  = filterRequest("profile_type"); // 0 أو 1
$working_days  = filterRequest("working_days"); // أيام الدوام (نص)

if (!$id_clinic) {
    echo json_encode(["status" => "fail", "message" => "يرجى إرسال id_clinic"]);
    exit;
}

// جلب بيانات الملف الحالي
$stmtCheck = $con->prepare("SELECT * FROM clinics_profiles WHERE id_clinic = ?");
$stmtCheck->execute([$id_clinic]);
$current = $stmtCheck->fetch(PDO::FETCH_ASSOC);

if (!$current) {
    echo json_encode(["status" => "fail", "message" => "ملف العيادة غير موجود"]);
    exit;
}

// تعويض القيم غير المرسلة بالقيم القديمة
$user_id      = $user_id ?: $current['user_id'];
$id_boss      = $id_boss ?: $current['id_boss'];
$clinic_name  = $clinic_name ?: $current['clinic_name'];
$address      = $address ?: $current['address'];
$website      = $website !== null ? $website : $current['website'];
$phone        = $phone ?: $current['phone'];
$description  = $description !== null ? $description : $current['description'];
$profile_type = ($profile_type === "0" || $profile_type === "1") ? $profile_type : $current['profile_type'];
$working_days = $working_days !== null ? $working_days : $current['working_days']; // ❗️جديد

// تحديث البيانات
$stmt = $con->prepare("UPDATE clinics_profiles SET 
    user_id = ?, 
    id_boss = ?, 
    clinic_name = ?, 
    address = ?, 
    website = ?, 
    phone = ?, 
    description = ?, 
    profile_type = ?,
    working_days = ?      -- ❗️جديد
    WHERE id_clinic = ?
");

$updated = $stmt->execute([
    $user_id,
    $id_boss,
    $clinic_name,
    $address,
    $website,
    $phone,
    $description,
    $profile_type,
    $working_days,
    $id_clinic
]);

if ($updated) {
    echo json_encode(["status" => "success", "message" => "تم تحديث ملف العيادة بنجاح"]);
} else {
    echo json_encode(["status" => "fail", "message" => "حدث خطأ أثناء التحديث"]);
}
