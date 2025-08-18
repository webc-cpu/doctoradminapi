<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

include ROOT_PATH . 'connect.php';
include_once ROOT_PATH . 'functions.php'; // لو فيها filterRequest

$search = "%" . filterRequest("search") . "%";
$id_boss = filterRequest("id_boss");
$user_id = filterRequest("user_id");

try {
    if ($id_boss) {
        $stmt = $con->prepare("SELECT * FROM treatment_settings WHERE treatment_name LIKE ? AND id_boss = ?");
        $stmt->execute([$search, $id_boss]);

    } elseif ($user_id) {
        $stmt = $con->prepare("SELECT * FROM treatment_settings WHERE treatment_name LIKE ? AND user_id = ?");
        $stmt->execute([$search, $user_id]);

    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Either id_boss or user_id is required."
        ]);
        exit;
    }

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $count = $stmt->rowCount();

    if ($count > 0) {
        echo json_encode([
            "status" => "success",
            "data" => $data
        ]);
    } else {
        echo json_encode([
            "status" => "fail",
            "message" => "No treatment settings found."
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
}
