<?php
require_once "connect.php";

$stmt = $con->prepare("SELECT 1");
$stmt->execute();
echo json_encode(["message" => "Connection successful"]);
// محمد