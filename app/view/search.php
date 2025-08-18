<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");

include ROOT_PATH . 'connect.php';

$id     = filterRequest("id");
$type   = filterRequest("type"); // boss or user or secretary
$table  = filterRequest("table");
$search = filterRequest("search");

if (!$id || !$type || !$table || !$search) {
    echo json_encode(['status' => 'error', 'message' => 'بيانات ناقصة']);
    exit;
}

$searchableFields = [
    'users' => 'user_name',
    'patients' => 'patient_name',
    'sessions' => 'session_note',
    'treatment' => 'treatment_note',
    'appointments' => 'appointment_note',
];

if (!isset($searchableFields[$table])) {
    echo json_encode(['status' => 'error', 'message' => 'الجدول غير مدعوم']);
    exit;
}

$column = $searchableFields[$table];

try {
    if ($type === 'boss') {
        if ($table === 'users') {
            $stmt = $con->prepare("SELECT * FROM users WHERE boss_user = ? AND $column LIKE ?");
            $stmt->execute([$id, "%$search%"]);
        } elseif ($table === 'patients') {
            $stmt = $con->prepare("
                SELECT p.* FROM patients p 
                INNER JOIN users u ON p.user_patient = u.user_id 
                WHERE u.boss_user = ? AND p.$column LIKE ?
            ");
            $stmt->execute([$id, "%$search%"]);
        } elseif ($table === 'sessions') {
            $stmt = $con->prepare("
                SELECT s.* FROM sessions s 
                INNER JOIN patients p ON s.patient_id = p.patient_id 
                INNER JOIN users u ON p.user_patient = u.user_id 
                WHERE u.boss_user = ? AND s.$column LIKE ?
            ");
            $stmt->execute([$id, "%$search%"]);
        } elseif ($table === 'treatment') {
            $stmt = $con->prepare("
                SELECT t.* FROM treatment t
                INNER JOIN sessions s ON t.session_id = s.session_id
                INNER JOIN patients p ON s.patient_id = p.patient_id 
                INNER JOIN users u ON p.user_patient = u.user_id 
                WHERE u.boss_user = ? AND t.$column LIKE ?
            ");
            $stmt->execute([$id, "%$search%"]);
        } elseif ($table === 'appointments') {
            $stmt = $con->prepare("
                SELECT a.* FROM appointments a
                INNER JOIN secretaries s ON a.secretary_id = s.secretary_id
                WHERE s.boss_secretary = ? AND a.$column LIKE ?
            ");
            $stmt->execute([$id, "%$search%"]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'الجدول غير مدعوم للبوس']);
            exit;
        }

    } elseif ($type === 'user') {
        if ($table === 'patients') {
            $stmt = $con->prepare("SELECT * FROM patients WHERE user_patient = ? AND $column LIKE ?");
            $stmt->execute([$id, "%$search%"]);
        } elseif ($table === 'sessions') {
            $stmt = $con->prepare("
                SELECT s.* FROM sessions s
                INNER JOIN patients p ON s.patient_id = p.patient_id 
                WHERE p.user_patient = ? AND s.$column LIKE ?
            ");
            $stmt->execute([$id, "%$search%"]);
        } elseif ($table === 'treatment') {
            $stmt = $con->prepare("
                SELECT t.* FROM treatment t
                INNER JOIN sessions s ON t.session_id = s.session_id
                INNER JOIN patients p ON s.patient_id = p.patient_id 
                WHERE p.user_patient = ? AND t.$column LIKE ?
            ");
            $stmt->execute([$id, "%$search%"]);
        } elseif ($table === 'appointments') {
            $stmt = $con->prepare("
                SELECT * FROM appointments WHERE user_id = ? AND $column LIKE ?
            ");
            $stmt->execute([$id, "%$search%"]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'الجدول غير مدعوم لليوزر']);
            exit;
        }

    } elseif ($type === 'secretary') {
        if ($table === 'appointments') {
            $stmt = $con->prepare("SELECT * FROM appointments WHERE secretary_id = ? AND $column LIKE ?");
            $stmt->execute([$id, "%$search%"]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'السكرتيرة تبحث فقط بالمواعيد']);
            exit;
        }

    } else {
        echo json_encode(['status' => 'error', 'message' => 'نوع المستخدم غير معروف']);
        exit;
    }

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['status' => 'success', 'results' => $data]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'خطأ: ' . $e->getMessage()]);
}
