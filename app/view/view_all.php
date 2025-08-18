<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");

include ROOT_PATH . 'connect.php';

$id = filterRequest('id');

if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'الرقم غير موجود']);
    exit;
}

try {
    $entities = [
        'boss' => ['table' => 'boss', 'column' => 'id_boss'],
        'user' => ['table' => 'users', 'column' => 'user_id'],
        'secretary' => ['table' => 'secretaries', 'column' => 'secretary_id']
    ];

    $found = false;

    foreach ($entities as $type => $info) {
        $stmt = $con->prepare("SELECT * FROM {$info['table']} WHERE {$info['column']} = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $found = true;

            switch ($type) {
                case 'boss':
                    $bossData = $row;

                    // إعدادات العلاج
                    $stmt = $con->prepare("SELECT * FROM treatment_settings WHERE id_boss = ?");
                    $stmt->execute([$id]);
                    $treatment_settings = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    // السكرتيرات ومواعيدهم
                    $stmt = $con->prepare("SELECT * FROM secretaries WHERE boss_secretary = ?");
                    $stmt->execute([$id]);
                    $secretaries = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($secretaries as &$secretary) {
                        $secretaryId = $secretary['secretary_id'];
                        $stmt = $con->prepare("SELECT * FROM appointments WHERE secretary_id = ?");
                        $stmt->execute([$secretaryId]);
                        $secretary['appointments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    }

                    // المستخدمين ومرضاهم وجلساتهم وعلاجاتهم
                    $stmt = $con->prepare("SELECT * FROM users WHERE boss_user = ?");
                    $stmt->execute([$id]);
                    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($users as &$user) {
                        $uid = $user['user_id'];

                        $stmt = $con->prepare("SELECT * FROM patients WHERE user_patient = ?");
                        $stmt->execute([$uid]);
                        $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        foreach ($patients as &$patient) {
                            $pid = $patient['patient_id'];

                            $stmt = $con->prepare("SELECT * FROM sessions WHERE patient_id = ?");
                            $stmt->execute([$pid]);
                            $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            foreach ($sessions as &$session) {
                                $sessionId = $session['session_id'];

                                $stmt = $con->prepare("SELECT * FROM treatment WHERE session_id = ?");
                                $stmt->execute([$sessionId]);
                                $treatments = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                foreach ($treatments as &$treatment) {
                                    $treatmentId = $treatment['treatment_id'];
                                    $stmt = $con->prepare("SELECT * FROM treatment_images WHERE treatment_id = ?");
                                    $stmt->execute([$treatmentId]);
                                    $treatment['images'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                }

                                $session['treatments'] = $treatments;
                            }

                            $patient['sessions'] = $sessions;
                        }

                        $user['patients'] = $patients;
                    }

                    echo json_encode([
                        'status' => 'success',
                        'type' => 'boss',
                        'data' => $bossData,
                        'treatment_settings' => $treatment_settings,
                        'secretaries' => $secretaries,
                        'users' => $users
                    ]);
                    break;

                case 'user':
                    $userData = $row;

                    $stmt = $con->prepare("SELECT * FROM patients WHERE user_patient = ?");
                    $stmt->execute([$id]);
                    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($patients as &$patient) {
                        $pid = $patient['patient_id'];

                        $stmt = $con->prepare("SELECT * FROM sessions WHERE patient_id = ?");
                        $stmt->execute([$pid]);
                        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        foreach ($sessions as &$session) {
                            $sessionId = $session['session_id'];

                            $stmt = $con->prepare("SELECT * FROM treatment WHERE session_id = ?");
                            $stmt->execute([$sessionId]);
                            $treatments = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            foreach ($treatments as &$treatment) {
                                $treatmentId = $treatment['treatment_id'];
                                $stmt = $con->prepare("SELECT * FROM treatment_images WHERE treatment_id = ?");
                                $stmt->execute([$treatmentId]);
                                $treatment['images'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            }

                            $session['treatments'] = $treatments;
                        }

                        $patient['sessions'] = $sessions;
                    }

                    $stmt = $con->prepare("SELECT * FROM appointments WHERE user_id = ?");
                    $stmt->execute([$id]);
                    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    echo json_encode([
                        'status' => 'success',
                        'type' => 'user',
                        'data' => $userData,
                        'patients' => $patients,
                        'appointments' => $appointments
                    ]);
                    break;

                case 'secretary':
                    $secretaryData = $row;

                    $stmt = $con->prepare("SELECT * FROM appointments WHERE secretary_id = ?");
                    $stmt->execute([$id]);
                    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    echo json_encode([
                        'status' => 'success',
                        'type' => 'secretary',
                        'data' => $secretaryData,
                        'appointments' => $appointments
                    ]);
                    break;
            }
            break;
        }
    }

    if (!$found) {
        echo json_encode(['status' => 'error', 'message' => 'لم يتم العثور على البيانات']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'خطأ في الاتصال: ' . $e->getMessage()]);
}
