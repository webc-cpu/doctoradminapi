<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include ROOT_PATH . 'connect.php';

try {
    $stmt = $con->prepare("SELECT * FROM roles");
    $stmt->execute();
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "data" => $roles
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "status" => "fail",
        "message" => $e->getMessage()
    ]);
}
