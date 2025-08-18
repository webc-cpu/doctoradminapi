<?php
header("Content-Type: application/json");
include ROOT_PATH . 'connect.php';

try {
    $stmt = $con->query("SELECT * FROM settings_master");
    $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "data" => $settings
    ]);
} catch (PDOException $e) {
    echo json_encode([
        "status" => "fail",
        "message" => $e->getMessage()
    ]);
}
