<?php
include ROOT_PATH . 'connect.php'; // ربط الاتصال
require_once ROOT_PATH . 'libs/SimpleXLSXGen.php'; 

$user_id = filterRequest("user_id");

// جلب المرضى التابعين للمستخدم
$stmt = $con->prepare("SELECT * FROM patients WHERE user_patient = ?");
$stmt->execute([$user_id]);
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

// دالة الترتيب حسب الخريطة
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

// خريطة الأعمدة
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
    'user_patient' => 'معرف الطبيب',
];

$sessionsMap = [
    'session_id' => 'رقم الجلسة',
    'session_name' => 'اسم الجلسة',
    'session_date' => 'تاريخ الجلسة',
    'session_note' => 'الملاحظات',
    'patient_id' => 'معرف المريض',
    'user_id' => 'معرف الطبيب',
];

$treatmentsMap = [
    'treatment_id' => 'رقم العلاج',
    'treatment_name' => 'اسم العلاج',
    'treatment_card' => 'بطاقة العلاج',
    'treatment_number' => 'رقم العلاج',
    'treatment_date' => 'تاريخ العلاج',
    'treatment_payment' => 'دفعة العلاج',
    'treatment_total' => 'إجمالي الدفعة',
    'treatment_note' => 'الملاحظات',
    'patient_treatment' => 'معرف المريض',
    'user_treatment' => 'معرف الطبيب',
    'session_id' => 'معرف الجلسة',
];

// إنشاء ملف Excel
$xlsx = new \Shuchkin\SimpleXLSXGen();
$xlsx->addSheet(formatSheetWithMap($patients, $patientsMap), 'المرضى');
$xlsx->addSheet(formatSheetWithMap($sessions, $sessionsMap), 'الجلسات');
$xlsx->addSheet(formatSheetWithMap($treatments, $treatmentsMap), 'العلاجات');

$xlsx->downloadAs("بيانات_الطبيب_$user_id.xlsx");
exit;
