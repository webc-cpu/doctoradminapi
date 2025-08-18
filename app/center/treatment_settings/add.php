<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

include ROOT_PATH . 'connect.php';
include_once ROOT_PATH . 'functions.php'; // لو فيها filterRequest

$treatment_name  = filterRequest("treatment_name");
$treatment_price = filterRequest("treatment_price");
$id_boss        = filterRequest("id_boss");
$user_id        = filterRequest("user_id");

// تحقق من وجود أحد المعرفين (بوس أو يوزر)
if (!$id_boss && !$user_id) {
    echo json_encode([
        "status" => "error",
        "message" => "Either id_boss or user_id is required."
    ]);
    exit;
}

try {
    if ($id_boss) {
        $stmt = $con->prepare("INSERT INTO treatment_settings (treatment_name, treatment_price, id_boss) VALUES (?, ?, ?)");
        $isInserted = $stmt->execute([$treatment_name, $treatment_price, $id_boss]);
    } else {
        $stmt = $con->prepare("INSERT INTO treatment_settings (treatment_name, treatment_price, user_id) VALUES (?, ?, ?)");
        $isInserted = $stmt->execute([$treatment_name, $treatment_price, $user_id]);
    }

    if ($isInserted) {
        echo json_encode(["status" => "success", "message" => "Treatment setting added successfully."]);
    } else {
        echo json_encode(["status" => "fail", "message" => "Failed to add treatment setting."]);
    }

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
}
