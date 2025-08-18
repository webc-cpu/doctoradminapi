<?php


function loadEnv($path = __DIR__ . '/.env') {
    if (!file_exists($path)) {
        return false;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // تجاهل التعليقات والأسطر الفارغة
        if (strpos(trim($line), '#') === 0) continue;

        // تقسيم السطر على =
        $parts = explode('=', $line, 2);
        if (count($parts) != 2) continue;

        $key = trim($parts[0]);
        $value = trim($parts[1]);

        // إزالة علامات الاقتباس إن وجدت
        if (preg_match('/^"(.*)"$/', $value, $matches) || preg_match("/^'(.*)'$/", $value, $matches)) {
            $value = $matches[1];
        }

        // تعيين المتغيرات في $_ENV و getenv و $_SERVER
        $_ENV[$key] = $value;
        putenv("$key=$value");
        $_SERVER[$key] = $value;
    }
    return true;
}






function filterRequest($key, $type = 'text') {
    // قراءة جسم الطلب إذا موجود
    $rawData = file_get_contents('php://input');
    $jsonData = json_decode($rawData, true);

    $value = null;

    if (isset($_GET[$key])) {
        $value = $_GET[$key];
    } elseif (isset($_POST[$key])) {
        $value = $_POST[$key];
    } elseif (is_array($jsonData) && isset($jsonData[$key])) {
        $value = $jsonData[$key];
    } else {
        // لدعم PUT/DELETE بصيغة key=value
        parse_str($rawData, $parsedRaw);
        if (isset($parsedRaw[$key])) {
            $value = $parsedRaw[$key];
        } else {
            return null;
        }
    }

    if ($type === 'file') {
        if (isset($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK) {
            return $_FILES[$key];
        } else {
            return null;
        }
    }

    if (is_string($value)) {
        $value = strip_tags($value);
        if (preg_match('/<\?(php|=)?/i', $value)) {
            return null;
        }
        $value = htmlspecialchars(trim($value), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    return $value;
}




// دالة لتحديث حالة البوس بالاعتماد على تاريخ البداية والنهاية 

function updateBossStatus(PDO $conn, int $boss_id) {
    try {
        $stmt = $conn->prepare("SELECT * FROM boss WHERE id_boss = ?");
        $stmt->execute([$boss_id]);
        $boss = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$boss) return;

        $today = date('Y-m-d');
        $startDate = $boss['boss_start_date'];
        $endDate = $boss['boss_end_date'];

        // احسب عدد اليوزر لهالبوس
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE boss_user = ?");
        $stmt->execute([$boss_id]);
        $userCount = $stmt->fetchColumn();

        // حدث عدد اليوزر
        $conn->prepare("UPDATE boss SET user_number = ? WHERE id_boss = ?")
             ->execute([$userCount, $boss_id]);

        // منطق تحديد الحالة
        $dayDiff = (strtotime($endDate) - strtotime($startDate)) / (60 * 60 * 24);

        if ($endDate > $today) {
            if ($dayDiff == 3) {
                $status = 'مفعل ك ضيف';
            } else {
                $status = 'مفعل';
            }
        } else {
            if ($dayDiff == 3) {
                $status = 'يرجى الاشتراك';
            } else {
                $status = 'يرجى تجديد الاشتراك';
            }
        }

        // تحديث الحالة
        $conn->prepare("UPDATE boss SET is_active = ? WHERE id_boss = ?")
             ->execute([$status, $boss_id]);

    } catch (PDOException $e) {
        // يمكن تطبع الخطأ للتصحيح
        // echo $e->getMessage();
    }
}

function updateUserStatusById(PDO $conn, int $user_id) {
    try {
        $stmt = $conn->prepare("SELECT user_start_date, user_end_date FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) return;

        $today = date('Y-m-d');
        $startDate = $user['user_start_date'];
        $endDate = $user['user_end_date'];

        $dayDiff = (strtotime($endDate) - strtotime($startDate)) / (60 * 60 * 24);

        if ($endDate > $today) {
            if ($dayDiff == 3) {
                $status = 'مفعل ك ضيف';
            } else {
                $status = 'مفعل';
            }
        } else {
            if ($dayDiff == 3) {
                $status = 'يرجى الاشتراك';
            } else {
                $status = 'يرجى تجديد الاشتراك';
            }
        }

        $conn->prepare("UPDATE users SET is_active = ? WHERE user_id = ?")
             ->execute([$status, $user_id]);

    } catch (PDOException $e) {
        // خطأ ممكن تسجله أو تعرضه إذا بدك
    }
}



// دالة لتحديث الاسعار في تيبل المريض 

function updatePatientTheRest($con, $patient_id) {
    $stmt = $con->prepare("
        SELECT 
            IFNULL(SUM(treatment_total), 0) AS total,
            IFNULL(SUM(treatment_payment), 0) AS payments
        FROM treatment
        WHERE patient_treatment = ?
    ");
    $stmt->execute([$patient_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $total    = $result['total'];
    $payments = $result['payments'];
    $rest     = $total - $payments;

    // فقط حدث إذا كان total أو payments أكبر من صفر
    if ($total > 0 || $payments > 0) {
        $stmtUpdate = $con->prepare("
            UPDATE patients 
            SET 
                patient_total_total     = ?,
                patient_total_paymensts = ?,
                patient_the_rest        = ?
            WHERE patient_id = ?
        ");
        $stmtUpdate->execute([$total, $payments, $rest, $patient_id]);
    }
}

function insertDefaultSettingsForBoss($id_boss, $pdo) {
    $sql = "
        INSERT INTO boss_settings (id_boss, setting_key, label, setting_value)
        SELECT :id_boss, setting_key, label, default_value
        FROM settings_master
        WHERE NOT EXISTS (
            SELECT 1 FROM boss_settings 
            WHERE id_boss = :id_boss AND setting_key = settings_master.setting_key
        );
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id_boss' => $id_boss]);
}

function updatePatientStatistics(PDO $pdo, int $patient_id): bool|string {
    try {
        // تأكد وجود صف في patient_statistics للمريض
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM patient_statistics WHERE patient_id = ?");
        $stmt->execute([$patient_id]);
        $exists = $stmt->fetchColumn();

        if ($exists == 0) {
            // إنشاء صف جديد لكن بدون القيم الفعلية (سيتم التحديث بعدها)
            $stmtInsert = $pdo->prepare("INSERT INTO patient_statistics (patient_id) VALUES (?)");
            $stmtInsert->execute([$patient_id]);
        }

        // 1. جمع treatment_payment من جدول treatment مع patient_treatment = patient_id
        $stmt = $pdo->prepare("SELECT IFNULL(SUM(treatment_payment),0) as total_treatment_payment FROM treatment WHERE patient_treatment = ?");
        $stmt->execute([$patient_id]);
        $total_treatment_payment = (float)$stmt->fetchColumn();

        // 2. جمع amount من جدول payments
        $stmt = $pdo->prepare("SELECT IFNULL(SUM(amount),0) as total_payment_amount FROM payments WHERE patient_id = ?");
        $stmt->execute([$patient_id]);
        $total_payment_amount = (float)$stmt->fetchColumn();

        $amount_paid = $total_treatment_payment + $total_payment_amount;

        // 3. جمع treatment_total من جدول treatment
        $stmt = $pdo->prepare("SELECT IFNULL(SUM(treatment_total),0) as sum_treatment_total FROM treatment WHERE patient_treatment = ?");
        $stmt->execute([$patient_id]);
        $sum_treatment_total = (float)$stmt->fetchColumn();

        // 4. جمع الأرقام من treatment_note في جدول treatment
        $stmt = $pdo->prepare("SELECT treatment_note FROM treatment WHERE patient_treatment = ?");
        $stmt->execute([$patient_id]);
        $notes = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $sum_note_numbers = 0;
        foreach ($notes as $note) {
            if ($note) {
                preg_match_all('/\d+(\.\d+)?/', $note, $matches);
                foreach ($matches[0] as $num) {
                    $sum_note_numbers += (float)$num;
                }
            }
        }

        $total_treatment_cost = $sum_treatment_total + $sum_note_numbers;

        // 5. نحسب المتبقي
        $remaining_amount = $total_treatment_cost - $amount_paid;

        // 6. تحديد قيمة is_paid
        $is_paid = ($remaining_amount == 0) ? 1 : 0;

        // 7. تحديث جدول patient_statistics سواء كان الصف جديد أو موجود
        $stmt = $pdo->prepare("
            UPDATE patient_statistics SET
                amount_paid = ?,
                total_treatment_cost = ?,
                remaining_amount = ?,
                is_paid = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE patient_id = ?
        ");

        $stmt->execute([
            $amount_paid,
            $total_treatment_cost,
            $remaining_amount,
            $is_paid,
            $patient_id
        ]);

        return true;
    } catch (Exception $e) {
        error_log("updatePatientStatistics error: " . $e->getMessage());
        return $e->getMessage();
    }
}




// function calculate_total_treatment_cost($con, $patient_id) {
//     $total = 0;

//     // جمع treatment_total
//     $stmt = $con->prepare("SELECT IFNULL(SUM(treatment_total), 0) FROM treatment WHERE patient_treatment = ?");
//     $stmt->execute([$patient_id]);
//     $total += $stmt->fetchColumn();

//     // جمع كل الأرقام من treatment_note
//     $stmt = $con->prepare("SELECT treatment_note FROM treatment WHERE patient_treatment = ?");
//     $stmt->execute([$patient_id]);

//     while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
//         preg_match_all('/\d+/', $row['treatment_note'], $matches);
//         foreach ($matches[0] as $number) {
//             $total += floatval($number);
//         }
//     }

//     return round($total, 2);
// }

// function calculate_total_paid($con, $patient_id) {
//     $total = 0;

//     // جمع treatment_payment
//     $stmt = $con->prepare("SELECT IFNULL(SUM(treatment_payment), 0) FROM treatment WHERE patient_treatment = ?");
//     $stmt->execute([$patient_id]);
//     $total += $stmt->fetchColumn();

//     // جمع payments.amount
//     $stmt = $con->prepare("SELECT IFNULL(SUM(amount), 0) FROM payments WHERE patient_id = ?");
//     $stmt->execute([$patient_id]);
//     $total += $stmt->fetchColumn();

//     return round($total, 2);
// }

// function updatePatientStatistics($con, $patient_id) {
//     $total_cost = calculate_total_treatment_cost($con, $patient_id);
//     $total_paid = calculate_total_paid($con, $patient_id);
//     $remaining  = $total_cost - $total_paid;
//     $is_paid    = $remaining <= 0 ? 1 : 0;

//     $stmt_check = $con->prepare("SELECT COUNT(*) FROM patient_statistics WHERE patient_id = ?");
//     $stmt_check->execute([$patient_id]);
//     $exists = $stmt_check->fetchColumn();

//     if ($exists == 0) {
//         $stmt_insert = $con->prepare("INSERT INTO patient_statistics (
//             patient_id, total_treatment_cost, amount_paid, remaining_amount, is_paid
//         ) VALUES (?, ?, ?, ?, ?)");
//         $stmt_insert->execute([$patient_id, $total_cost, $total_paid, $remaining, $is_paid]);
//     } else {
//         $stmt_update = $con->prepare("UPDATE patient_statistics SET 
//             total_treatment_cost = ?, 
//             amount_paid = ?, 
//             remaining_amount = ?, 
//             is_paid = ?, 
//             updated_at = CURRENT_TIMESTAMP 
//             WHERE patient_id = ?");
//         $stmt_update->execute([$total_cost, $total_paid, $remaining, $is_paid, $patient_id]);
//     }
// }

// دالة لتفعيل الحساب البوس 

// function applyBossUpdates($con) {
//     $stmt = $con->prepare("SELECT id_boss, is_active FROM boss_updates");
//     $stmt->execute();
//     $updates = $stmt->fetchAll(PDO::FETCH_ASSOC);

//     if (!$updates) {
//         return;
//     }

//     $updateStmt = $con->prepare("UPDATE boss SET is_active = ? WHERE id_boss = ?");
//     foreach ($updates as $row) {
//         $updateStmt->execute([$row['is_active'], $row['id_boss']]);
//     }

//     $con->prepare("DELETE FROM boss_updates")->execute();
// }






// نسخة 1
// function filterRequest($key, $type = 'text') {
//     if ($type === 'file') {
//         // صور من form-data
//         if (isset($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK) {
//             return $_FILES[$key];
//         } else {
//             return null;
//         }
//     } else {
//         // محاولات للحصول على القيمة من JSON أو POST أو GET
//         $jsonData = json_decode(file_get_contents("php://input"), true);

//         if (is_array($jsonData) && isset($jsonData[$key])) {
//             return htmlspecialchars(trim($jsonData[$key]));
//         } elseif (isset($_POST[$key])) {
//             return htmlspecialchars(trim($_POST[$key]));
//         } elseif (isset($_GET[$key])) {
//             return htmlspecialchars(trim($_GET[$key]));
//         } else {
//             return null;
//         }
//     }
// }


// نسخة 2

// function filterRequest($key, $type = 'text', $requestData = []) {
//     if ($type === 'file') {
//         if (isset($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK) {
//             return $_FILES[$key];
//         } else {
//             return null;
//         }
//     } else {
//         if (is_array($requestData) && isset($requestData[$key])) {
//             return htmlspecialchars(trim($requestData[$key]));
//         } elseif (isset($_POST[$key])) {
//             return htmlspecialchars(trim($_POST[$key]));
//         } elseif (isset($_GET[$key])) {
//             return htmlspecialchars(trim($_GET[$key]));
//         } else {
//             return null;
//         }
//     }
// }

