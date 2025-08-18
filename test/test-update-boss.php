<?php
include "connect.php";       // الاتصال بقاعدة البيانات    // لازم تكون دالة updateBossStatus موجودة هون

// جلب كل المدراء (boss_id)
$stmt = $con->prepare("SELECT id_boss FROM boss");
$stmt->execute();
$bosses = $stmt->fetchAll(PDO::FETCH_COLUMN);

if ($bosses) {
    foreach ($bosses as $boss_id) {
        updateBossStatus($con, $boss_id);
    }
    echo "✅ تم تحديث عدد المستخدمين لجميع المدراء (" . count($bosses) . ")";
} else {
    echo "❌ لا يوجد مدراء بالتسجيلات.";
}
