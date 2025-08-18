<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, PUT, DELETE, OPTIONS");

include ROOT_PATH . 'connect.php';

// دالة لجلب قيمة من الطلب سواء POST أو JSON خام
// function filterRequest($key) {
//     // أولاً من JSON خام لو Content-Type application/json
//     $data = json_decode(file_get_contents('php://input'), true);
//     if ($data && isset($data[$key])) {
//         return $data[$key];
//     }
//     // إذا مش موجود JSON يرجع من POST
//     return $_POST[$key] ?? null;
// }

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':  // إضافة ملف جديد
        $user_id    = filterRequest('user_id');
        $id_boss    = filterRequest('id_boss');
        $clinic_name = filterRequest('clinic_name');
        $address     = filterRequest('address');
        $website     = filterRequest('website');
        $phone       = filterRequest('phone');
        $description = filterRequest('description');

        if (!$clinic_name || !$address || !$phone || (!$user_id && !$id_boss)) {
            echo json_encode(['status' => 'fail', 'message' => 'الحقول المطلوبة ناقصة']);
            exit;
        }

        $stmt = $con->prepare("INSERT INTO clinics_profiles (user_id, id_boss, clinic_name, address, website, phone, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $res = $stmt->execute([$user_id, $id_boss, $clinic_name, $address, $website, $phone, $description]);

        if ($res) {
            echo json_encode(['status' => 'success', 'message' => 'تم إضافة ملف العيادة', 'id_clinic' => $con->lastInsertId()]);
        } else {
            echo json_encode(['status' => 'fail', 'message' => 'حدث خطأ أثناء الإضافة']);
        }
        break;

    case 'PUT':  // تعديل ملف حسب id_clinic
        $id_clinic   = filterRequest('id_clinic');
        $clinic_name = filterRequest('clinic_name');
        $address     = filterRequest('address');
        $website     = filterRequest('website');
        $phone       = filterRequest('phone');
        $description = filterRequest('description');

        if (!$id_clinic) {
            echo json_encode(['status' => 'fail', 'message' => 'يرجى إرسال id_clinic']);
            exit;
        }

        // تحديث الحقول إذا أرسلت
        $fields = [];
        $params = [];

        if ($clinic_name !== null) { $fields[] = "clinic_name = ?"; $params[] = $clinic_name; }
        if ($address !== null) { $fields[] = "address = ?"; $params[] = $address; }
        if ($website !== null) { $fields[] = "website = ?"; $params[] = $website; }
        if ($phone !== null) { $fields[] = "phone = ?"; $params[] = $phone; }
        if ($description !== null) { $fields[] = "description = ?"; $params[] = $description; }

        if (count($fields) === 0) {
            echo json_encode(['status' => 'fail', 'message' => 'لا توجد حقول للتعديل']);
            exit;
        }

        $params[] = $id_clinic;
        $sql = "UPDATE clinics_profiles SET " . implode(", ", $fields) . " WHERE id_clinic = ?";

        $stmt = $con->prepare($sql);
        $res = $stmt->execute($params);

        if ($res) {
            echo json_encode(['status' => 'success', 'message' => 'تم تحديث ملف العيادة']);
        } else {
            echo json_encode(['status' => 'fail', 'message' => 'حدث خطأ أثناء التحديث']);
        }
        break;

    case 'DELETE': // حذف ملف حسب id_clinic
        $id_clinic = filterRequest('id_clinic');

        if (!$id_clinic) {
            echo json_encode(['status' => 'fail', 'message' => 'يرجى إرسال id_clinic للحذف']);
            exit;
        }

        $stmt = $con->prepare("DELETE FROM clinics_profiles WHERE id_clinic = ?");
        $res = $stmt->execute([$id_clinic]);

        if ($res) {
            echo json_encode(['status' => 'success', 'message' => 'تم حذف ملف العيادة']);
        } else {
            echo json_encode(['status' => 'fail', 'message' => 'حدث خطأ أثناء الحذف']);
        }
        break;

    case 'OPTIONS': // دعم preflight request
        http_response_code(200);
        break;

    default:
        echo json_encode(['status' => 'fail', 'message' => 'الطريقة غير مدعومة']);
        break;
}
