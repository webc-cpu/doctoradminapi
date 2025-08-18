<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

include ROOT_PATH . 'connect.php';
include_once ROOT_PATH . 'functions.php'; // فيها filterRequest

$id_boss = filterRequest("id_boss");
$user_id = filterRequest("user_id");

try {
    if ($id_boss) {
        // عرض إعدادات البوس
        $stmt = $con->prepare("SELECT * FROM treatment_settings WHERE id_boss = ?");
        $stmt->execute([$id_boss]);
    } elseif ($user_id) {
        // عرض إعدادات اليوزر
        $stmt = $con->prepare("SELECT * FROM treatment_settings WHERE user_id = ?");
        $stmt->execute([$user_id]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Either id_boss or user_id is required."
        ]);
        exit;
    }

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode([
        "status" => "success",
        "data" => $data
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
}
