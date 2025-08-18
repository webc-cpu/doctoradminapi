<?php


header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

include ROOT_PATH . 'connect.php';

$user_id      = filterRequest("user_id");
$id_boss      = filterRequest("id_boss");
$secretary_id = filterRequest("secretary_id");
$search_query = filterRequest("search"); // الاسم أو رقم البطاقة

// مصفوفة معرفات المستخدمين
$userIds = [];

if ($id_boss) {
    // حالة وجود رئيس - جلب جميع المستخدمين التابعين له
    $stmt = $con->prepare("SELECT user_id FROM users WHERE boss_user = ?");
    $stmt->execute([$id_boss]);
    $userIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

} elseif ($secretary_id) {
    // حالة السكرتيرة - جلب الرئيس أولاً إن وجد
    $stmt = $con->prepare("SELECT boss_secretary, user_secretary FROM secretaries WHERE secretary_id = ?");
    $stmt->execute([$secretary_id]);
    $secretary_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $boss_id = $secretary_data['boss_secretary'];
    $user_secretary = $secretary_data['user_secretary'];
    
    if ($boss_id) {
        // إذا كان للسكرتيرة رئيس - جلب مستخدمي هذا الرئيس
        $stmt = $con->prepare("SELECT user_id FROM users WHERE boss_user = ?");
        $stmt->execute([$boss_id]);
        $userIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } elseif ($user_secretary) {
        // إذا لم يكن للسكرتيرة رئيس - جلب مرضى المستخدم الذي تتبعه السكرتيرة مباشرة
        $userIds = [$user_secretary];
    }

} elseif ($user_id) {
    // حالة المستخدم العادي - جلب مرضيه فقط
    $userIds = [$user_id];
} else {
    echo json_encode(["status" => "fail", "message" => "يجب إرسال id_boss أو secretary_id أو user_id"]);
    exit;
}

if (empty($userIds)) {
    echo json_encode(["status" => "fail", "message" => "لم يتم العثور على أي مستخدمين"]);
    exit;
}

// إعداد الاستعلام الديناميكي
$placeholders = implode(',', array_fill(0, count($userIds), '?'));
$sql = "SELECT * FROM patients WHERE user_patient IN ($placeholders)";

// البحث إذا تم تمرير search
$params = $userIds;
if ($search_query) {
    $sql .= " AND (patient_name LIKE ? OR patient_card LIKE ?)";
    $searchTerm = "%" . $search_query . "%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$stmt = $con->prepare($sql);
$stmt->execute($params);

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($data) {
    echo json_encode(["status" => "success", "data" => $data]);
} else {
    echo json_encode(["status" => "fail", "message" => "لا يوجد مرضى مطابقين"]);
}













// header("Access-Control-Allow-Origin: *");
// header("Access-Control-Allow-Headers: Content-Type, Authorization");
// header("Access-Control-Allow-Methods: POST, OPTIONS");

// include ROOT_PATH . 'connect.php';


// $user_id      = filterRequest("user_id");
// $id_boss      = filterRequest("id_boss");
// $secretary_id = filterRequest("secretary_id");
// $search_query = filterRequest("search"); // الاسم أو رقم البطاقة

// // مصفوفة معرفات المستخدمين
// $userIds = [];

// if ($id_boss) {
//     $stmt = $con->prepare("SELECT user_id FROM users WHERE boss_user = ?");
//     $stmt->execute([$id_boss]);
//     $userIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

// } elseif ($secretary_id) {
//     $stmt = $con->prepare("SELECT boss_secretary FROM secretaries WHERE secretary_id = ?");
//     $stmt->execute([$secretary_id]);
//     $boss_id = $stmt->fetchColumn();

//     if ($boss_id) {
//         $stmt = $con->prepare("SELECT user_id FROM users WHERE boss_user = ?");
//         $stmt->execute([$boss_id]);
//         $userIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
//     }

// } elseif ($user_id) {
//     $userIds = [$user_id];
// } else {
//     echo json_encode(["status" => "fail", "message" => "يجب إرسال id_boss أو secretary_id أو user_id"]);
//     exit;
// }

// if (empty($userIds)) {
//     echo json_encode(["status" => "fail", "message" => "لم يتم العثور على أي مستخدمين"]);
//     exit;
// }

// // إعداد الاستعلام الديناميكي
// $placeholders = implode(',', array_fill(0, count($userIds), '?'));
// $sql = "SELECT * FROM patients WHERE user_patient IN ($placeholders)";

// // البحث إذا تم تمرير search
// $params = $userIds;
// if ($search_query) {
//     $sql .= " AND (patient_name LIKE ? OR patient_card LIKE ?)";
//     $searchTerm = "%" . $search_query . "%";
//     $params[] = $searchTerm;
//     $params[] = $searchTerm;
// }

// $stmt = $con->prepare($sql);
// $stmt->execute($params);

// $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// if ($data) {
//     echo json_encode(["status" => "success", "data" => $data]);
// } else {
//     echo json_encode(["status" => "fail", "message" => "لا يوجد مرضى مطابقين"]);
// }
