<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

include ROOT_PATH . 'connect.php';


// فقط هذا هو المرسل
$setting_id = filterRequest("setting_id");

// تحقق من وجود setting_id
if (!$setting_id) {
    echo json_encode(["status" => "fail", "message" => "setting_id مطلوب"]);
    exit;
}

// جلب بيانات العلاج
$stmt = $con->prepare("SELECT * FROM treatment_settings WHERE setting_id = ?");
$stmt->execute([$setting_id]);
$treatment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$treatment) {
    echo json_encode(["status" => "not_found"]);
    exit;
}

// تجاهل أي محاولة لتعديل المالكين
unset($_POST['id_boss']);
unset($_POST['user_id']);

// إعداد الحقول للتحديث
$fields = [];
$params = [];

// فقط إذا تم إرسال قيم جديدة
$treatment_name  = filterRequest("treatment_name");
$treatment_price = filterRequest("treatment_price");

if (!empty($treatment_name)) {
    $fields[] = "treatment_name = ?";
    $params[] = $treatment_name;
}
if (!empty($treatment_price)) {
    $fields[] = "treatment_price = ?";
    $params[] = $treatment_price;
}

// تحديث فقط إذا في بيانات جديدة
if (count($fields) > 0) {
    $params[] = $setting_id;

    $sql = "UPDATE treatment_settings SET " . implode(", ", $fields) . " WHERE setting_id = ?";
    $stmt = $con->prepare($sql);
    $isUpdated = $stmt->execute($params);

    echo json_encode(["status" => $isUpdated ? "success" : "fail"]);
} else {
    echo json_encode(["status" => "no_data_to_update", "message" => "لم يتم إرسال بيانات للتعديل"]);
}
