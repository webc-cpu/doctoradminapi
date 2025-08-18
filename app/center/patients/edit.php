<?php
include ROOT_PATH . 'connect.php'; // ربط الاتصال + دوال مساعدة مثل filterRequest و updateBossStatus

$patient_id = filterRequest("patient_id");

// استعلام لجلب البيانات الحالية
$stmtSelect = $con->prepare("SELECT * FROM patients WHERE patient_id = ?");
$stmtSelect->execute([$patient_id]);
$currentData = $stmtSelect->fetch(PDO::FETCH_ASSOC);

// في حال المريض غير موجود
if (!$currentData) {
    echo json_encode(["status" => "fail", "message" => "المريض غير موجود"]);
    exit();
}

// جلب القيم الجديدة أو القديمة حسب وجودها
$user_patient   = filterRequest("user_patient")   ?: $currentData['user_patient'];
$patient_name   = filterRequest("patient_name")   ?: $currentData['patient_name'];
$Phone_Number   = filterRequest("Phone_Number")   ?: $currentData['Phone_Number'];
$Drug_Allergies = filterRequest("Drug_Allergies") ?: $currentData['Drug_Allergies'];
$Address        = filterRequest("Address")        ?: $currentData['Address'];
$Age            = filterRequest("Age")            ?: $currentData['Age'];
$Gender         = filterRequest("Gender")         ?: $currentData['Gender'];
$Pregnant       = filterRequest("Pregnant")       ?: $currentData['Pregnant'];
$Smoker         = filterRequest("Smoker")         ?: $currentData['Smoker'];
$patient_card   = filterRequest("patient_card")   ?: $currentData['patient_card'];
$patient_date   = filterRequest("patient_date")   ?: $currentData['patient_date'];

try {
    $con->beginTransaction();

    // تحديث بيانات المريض
    $stmtUpdate = $con->prepare("
        UPDATE `patients` 
        SET 
            `user_patient` = ?, 
            `patient_name` = ?, 
            `Phone_Number` = ?, 
            `Drug_Allergies` = ?, 
            `Address` = ?, 
            `Age` = ?, 
            `Gender` = ?, 
            `Pregnant` = ?, 
            `Smoker` = ?,
            `patient_card` = ?,
            `patient_date` = ?
        WHERE patient_id = ?
    ");

    $stmtUpdate->execute([
        $user_patient, $patient_name, $Phone_Number, $Drug_Allergies, $Address, $Age, $Gender,
        $Pregnant, $Smoker, $patient_card, $patient_date, $patient_id
    ]);

    $count = $stmtUpdate->rowCount();

    // إذا تغير user_patient فقط
    if ($user_patient != $currentData['user_patient']) {
        // تحديث user_id في جدول الجلسات المرتبطة بالمريض
        $stmt = $con->prepare("UPDATE sessions SET user_id = ? WHERE patient_id = ?");
        $stmt->execute([$user_patient, $patient_id]);

        // تحديث user_treatment في جدول العلاجات المرتبطة بجلسات المريض
        $stmt = $con->prepare("
            UPDATE treatment 
            SET user_treatment = ? 
            WHERE session_id IN (
                SELECT session_id  FROM sessions WHERE patient_id = ?
            )
        ");
        $stmt->execute([$user_patient, $patient_id]);
    }

    $con->commit();

    if ($count > 0) {
        // تحديث الرصيد
        updatePatientTheRest($con, $patient_id);

        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "fail", "message" => "لم يتم تعديل أي بيانات"]);
    }

} catch (PDOException $e) {
    $con->rollBack();
    echo json_encode(["status" => "fail", "message" => "خطأ في المعاملة", "error" => $e->getMessage()]);
}
