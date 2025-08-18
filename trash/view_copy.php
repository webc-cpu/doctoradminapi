<?php
include ROOT_PATH . 'connect.php'; // ربط الاتصال
// يحتوي على الاتصال + دوال المساعدة مثل filterRequest و updateBossStatus

$id_boss = intval(filterRequest('id_boss'));

if ($id_boss <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid or missing id_boss']);
    exit;
}

$sql = "
SELECT 
    u.id_user, u.name as user_name,
    p.id_patient, p.name as patient_name,
    s.id_session, s.session_date,
    t.id_treatment, t.treatment_name
FROM users u
LEFT JOIN patients p ON p.id_user = u.id_user
LEFT JOIN sessions s ON s.patient_id = p.id_patient
LEFT JOIN treatment t ON t.session_id = s.id_session
WHERE u.boss_id = :id_boss
ORDER BY u.id_user, p.id_patient, s.id_session, t.id_treatment
";

$stmt = $con->prepare($sql);
$stmt->execute(['id_boss' => $id_boss]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ... باقي الكود بدون تغيير


$response = [];
foreach ($results as $row) {
    $id_user = $row['id_user'];
    $patient_id = $row['id_patient'];
    $session_id = $row['id_session'];
    $treatment_id = $row['id_treatment'];

    if (!isset($response[$id_user])) {
        $response[$id_user] = [
            'id_user' => $id_user,
            'name' => $row['user_name'],
            'patients' => []
        ];
    }

    if ($patient_id && !isset($response[$id_user]['patients'][$patient_id])) {
        $response[$id_user]['patients'][$patient_id] = [
            'id_patient' => $patient_id,
            'name' => $row['patient_name'],
            'sessions' => []
        ];
    }

    if ($session_id && !isset($response[$id_user]['patients'][$patient_id]['sessions'][$session_id])) {
        $response[$id_user]['patients'][$patient_id]['sessions'][$session_id] = [
            'id_session' => $session_id,
            'session_date' => $row['session_date'],
            'treatment' => []
        ];
    }

    if ($treatment_id) {
        $response[$id_user]['patients'][$patient_id]['sessions'][$session_id]['treatment'][] = [
            'id_treatment' => $treatment_id,
            'treatment_name' => $row['treatment_name']
        ];
    }
}

function reindex($arr) {
    if (!is_array($arr)) return $arr;
    if (array_keys($arr) !== range(0, count($arr) -1)) {
        $arr = array_values($arr);
    }
    foreach ($arr as &$v) {
        if (is_array($v)) $v = reindex($v);
    }
    return $arr;
}

foreach ($response as &$user) {
    $user['patients'] = reindex($user['patients']);
    foreach ($user['patients'] as &$patient) {
        $patient['sessions'] = reindex($patient['sessions']);
    }
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(reindex($response), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
