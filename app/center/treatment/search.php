<?php
include ROOT_PATH . 'connect.php';

$user_id = filterRequest("user_id");
$search_query = filterRequest("search_query");
$patient_id = filterRequest("patient_id"); // استقبال patient_id

if (!empty($search_query)) {
    $stmt = $con->prepare("SELECT * FROM treatment WHERE `user_treatment` = ? AND `patient_treatment` = ? AND `treatment_name` LIKE ?");
    $stmt->execute(array($user_id, $patient_id, "%$search_query%"));
} else {
    $stmt = $con->prepare("SELECT * FROM treatment WHERE `user_treatment` = ? AND `patient_treatment` = ?");
    $stmt->execute(array($user_id, $patient_id));
}

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
$count = $stmt->rowCount();

if ($count > 0) {
    echo json_encode(array("status" => "success", "data" => $data));
} else {
    echo json_encode(array("status" => "fail", "message" => "No treatments found for this patient"));
}
?>