<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");

include ROOT_PATH . 'connect.php'; // فيه $con


$current_version = intval(filterRequest("current_version")); // رقم الإصدار الحالي المرسل من التطبيق

try {
    // نحصل على أعلى إصدار موجود أكبر من الإصدار الحالي
    $stmt = $con->prepare("SELECT MAX(version_code) AS max_version FROM app_version WHERE version_code > ?");
    $stmt->execute([$current_version]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result && $result['max_version']) {
        $latestVersion = $result['max_version'];

        // نجيب كل الروابط والمنصات يلي بتحمل هالإصدار
        $stmtLinks = $con->prepare("SELECT name, update_links FROM app_version WHERE version_code = ?");
        $stmtLinks->execute([$latestVersion]);
        $links = $stmtLinks->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            "update_available" => true,
            "message" => "يوجد إصدار جديد",
            "latest_version" => $latestVersion,
            "links" => $links
        ]);
    } else {
        echo json_encode([
            "update_available" => false,
            "message" => "لا يوجد إصدار جديد"
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        "error" => $e->getMessage()
    ]);
}
?>
