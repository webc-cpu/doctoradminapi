<?php
include ROOT_PATH . 'connect.php'; // ربط الاتصال
 // ربط الاتصال

require_once ROOT_PATH . 'libs/SimpleXLSXGen.php'; 

$boss_id = filterRequest("boss_id");

// جلب المستخدمين المرتبطين بالبوس
$stmt = $con->prepare("SELECT * FROM users WHERE boss_user = ?");
$stmt->execute([$boss_id]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// جلب المرضى المرتبطين بالمستخدمين
$user_ids = array_column($users, 'user_id');
if (!empty($user_ids)) {
    $placeholders = implode(',', array_fill(0, count($user_ids), '?'));
    $stmt = $con->prepare("SELECT * FROM patients WHERE user_patient IN ($placeholders)");
    $stmt->execute($user_ids);
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // جلب الجلسات المرتبطة بالمرضى
    $patient_ids = array_column($patients, 'patient_id');
    if (!empty($patient_ids)) {
        $placeholders_patients = implode(',', array_fill(0, count($patient_ids), '?'));
        $stmt = $con->prepare("SELECT * FROM sessions WHERE patient_id IN ($placeholders_patients)");
        $stmt->execute($patient_ids);
        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // جلب العلاجات المرتبطة بالجلسات
        $session_ids = array_column($sessions, 'session_id');
        if (!empty($session_ids)) {
            $placeholders_sessions = implode(',', array_fill(0, count($session_ids), '?'));
            $stmt = $con->prepare("SELECT * FROM treatment WHERE session_id IN ($placeholders_sessions)");
            $stmt->execute($session_ids);
            $treatments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $treatments = [];
        }
    } else {
        $sessions = [];
        $treatments = [];
    }
} else {
    $patients = [];
    $sessions = [];
    $treatments = [];
}

// جلب السكرتيرات المرتبطات بالبوس
$stmt = $con->prepare("SELECT * FROM secretaries WHERE boss_secretary = ?");
$stmt->execute([$boss_id]);
$secretaries = $stmt->fetchAll(PDO::FETCH_ASSOC);

// جلب المواعيد المرتبطة بالسكرتيرات
$secretary_ids = array_column($secretaries, 'id');
if (!empty($secretary_ids)) {
    $placeholders_secretaries = implode(',', array_fill(0, count($secretary_ids), '?'));
    $stmt = $con->prepare("SELECT * FROM appointments WHERE secretary_id IN ($placeholders_secretaries)");
    $stmt->execute($secretary_ids);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $appointments = [];
}

// دالة لترتيب وتصفيه البيانات حسب خريطة الأعمدة key=>header
function formatSheetWithMap($data, $columnsMap = []) {
    if (empty($data)) return [['لا يوجد بيانات']];

    $headers = array_values($columnsMap);
    $keys = array_keys($columnsMap);

    $rows = [];
    foreach ($data as $row) {
        $newRow = [];
        foreach ($keys as $key) {
            $newRow[] = isset($row[$key]) ? $row[$key] : '';
        }
        $rows[] = $newRow;
    }

    array_unshift($rows, $headers);
    return $rows;
}

/* ===== خرايط الأعمدة للعناوين والحقول ===== */

// المستخدمين
$usersMap = [
    'user_name' => 'اسم الطبيب',
    'user_email' => 'البريد الإلكتروني',
];

// المرضى
$patientsMap = [
    'patient_id' => 'معرف المريض',
    'patient_name' => 'اسم المريض',
    'Phone_Number' => 'رقم الهاتف',
    'Drug_Allergies' => 'الحساسية الدوائية',
    'Address' => 'العنوان',
    'Age' => 'العمر',
    'Gender' => 'الجنس',
    'Pregnant' => 'حامل',
    'Smoker' => 'مدخن',
    'patient_card' => 'بطاقة المريض',
    'patient_date' => 'تاريخ التسجيل',
    'patient_total_paymensts' => 'مجموع الدفعات',
    'patient_total_total' => 'الإجمالي الواجب دفعه',
    'patient_the_rest' => 'الباقي',
    'user_patient' => 'معرف الطبيب المسؤول',
];

// الجلسات
$sessionsMap = [
    'session_id' => 'رقم الجلسة',
    'session_name' => 'اسم الجلسة',
    'session_date' => 'تاريخ الجلسة',
    'session_note' => 'الملاحظات',
    'patient_id' => 'معرف المريض',
    'user_id' => 'معرف الطبيب',
];

// العلاجات
$treatmentsMap = [
    'treatment_id' => 'رقم العلاج',
    'treatment_name' => 'اسم العلاج',
    'treatment_card' => 'بطاقة العلاج',
    'treatment_number' => 'رقم العلاج',
    'treatment_date' => 'تاريخ العلاج',
    'treatment_payment' => 'دفعة العلاح',
    'treatment_total' => 'اجمالي الدفعة',
    'treatment_note' => 'الملاحظات',
    'patient_treatment' => 'معرف المريض',
    'user_treatment' => 'معرف الطبيب',
    'session_id' => 'معرف الجلسة',
   
];

// المواعيد
$appointmentsMap = [
    'appointment_id' => 'رقم الموعد',
    'patient_name' => 'اسم المريض',
    'phone_number' => 'رقم الهاتف',
    'appointment_date' => 'تاريخ الموعد',
    'appointment_time' => 'الوقت',
    'appointment_note' => 'الملاحظات',
    'patient_id' => 'معرف المريض',
    'secretary_id' => 'معرف السكرتيرة',
    'user_id' => 'معرف المستخدم',
];

/* ===== إنشاء ملف Excel ===== */

$xlsx = new \Shuchkin\SimpleXLSXGen();

$xlsx->addSheet(formatSheetWithMap($users, $usersMap), 'الأطباء');
$xlsx->addSheet(formatSheetWithMap($patients, $patientsMap), 'المرضى');
$xlsx->addSheet(formatSheetWithMap($sessions, $sessionsMap), 'الجلسات');
$xlsx->addSheet(formatSheetWithMap($treatments, $treatmentsMap), 'العلاجات');
$xlsx->addSheet(formatSheetWithMap($appointments, $appointmentsMap), 'المواعيد');

$xlsx->downloadAs("بيانات_المركز_$boss_id.xlsx");
exit;
