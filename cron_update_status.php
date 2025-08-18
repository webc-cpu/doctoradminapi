<?php
// cron_update_status.php

include "connect.php";      // الاتصال بقاعدة البيانات

try {
    // جلب كل البوسات وتحديثهم
    $stmt = $con->query("SELECT * FROM boss");
    $bosses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($bosses as $boss) {
        $boss_id = $boss['id_boss'];
        updateBossStatus($con, $boss_id); // استدعاء الدالة
    }

    // جلب كل اليوزرات وتحديثهم
    $stmt = $con->query("SELECT * FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($users as $user) {
        // تجاهل المستخدمين اللي البوس مالهم boss_user فاضي أو null
        if (!empty($user['boss_user'])) {
            $user_id = $user['user_id'];
            updateUserStatusById($con, $user_id); // استدعاء دالة تحديث الحالة لليوزر
        }
    }

    echo "تم تحديث جميع البوسات واليوزرات بنجاح بتاريخ " . date('Y-m-d H:i:s');

} catch (PDOException $e) {
    echo "خطأ في التحديث: " . $e->getMessage();
}
