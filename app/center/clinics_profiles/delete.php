<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST");


include_once ROOT_PATH . 'connect.php';

$id_clinic = filterRequest('id_clinic');

if (!$id_clinic) {
    echo json_encode(['status' => 'fail', 'message' => 'يرجى إرسال id_clinic']);
    exit;
}

$stmt = $con->prepare("DELETE FROM clinics_profiles WHERE id_clinic = ?");
$deleted = $stmt->execute([$id_clinic]);

if ($deleted) {
    echo json_encode(['status' => 'success', 'message' => 'تم الحذف بنجاح']);
} else {
    echo json_encode(['status' => 'fail', 'message' => 'فشل الحذف']);
}
