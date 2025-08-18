<?php

include_once __DIR__ . '/../connect.php'; // ربط الاتصال
require_once "../functions.php";

$token = filterRequest("token");

function showMessage($title, $color) {
    echo "<!DOCTYPE html>
    <html lang='ar' dir='rtl'>
    <head>
        <meta charset='UTF-8'>
        <title>تفعيل الحساب</title>
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background-color: #f7f7f7;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                direction: rtl;
            }
            .message-box {
                background-color: white;
                padding: 40px;
                border-radius: 15px;
                box-shadow: 0 0 15px rgba(0,0,0,0.1);
                text-align: center;
            }
            .message-box h2 {
                color: $color;
            }
        </style>
    </head>
    <body>
        <div class='message-box'>
            <h2>$title</h2>
        </div>
    </body>
    </html>";
    exit;
}

if (!$token) {
    showMessage("❌ رابط التفعيل غير صالح أو مفقود.", "#e74c3c");
}

// أولاً نحاول نفعّل بوس
$stmtBoss = $con->prepare("SELECT * FROM boss WHERE verify_token = ?");
$stmtBoss->execute([$token]);
$boss = $stmtBoss->fetch(PDO::FETCH_ASSOC);

if ($boss) {
    $update = $con->prepare("UPDATE boss 
                             SET verify_token = NULL, email_verified = 1, is_active = 1, boss_start_date = NOW() 
                             WHERE verify_token = ?");
    $update->execute([$token]);

    if ($update->rowCount() > 0) {
        if (empty($boss['boss_end_date'])) {
            $updateEndDate = $con->prepare("UPDATE boss 
                                            SET boss_end_date = DATE_ADD(boss_start_date, INTERVAL 3 DAY) 
                                            WHERE id_boss = ?");
            $updateEndDate->execute([$boss['id_boss']]);
        }

        updateBossStatus($con, $boss['id_boss']);
        showMessage("✅ تم تفعيل حساب المدير بنجاح!", "#2ecc71");
    } else {
        showMessage("⚠️ الرابط مستخدم مسبقاً أو غير صالح.", "#f1c40f");
    }
}

// إذا ما كان بوس، جرب على users
$stmtUser = $con->prepare("SELECT * FROM users WHERE verify_token = ?");
$stmtUser->execute([$token]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);

if ($user) {
    $update = $con->prepare("UPDATE users 
                             SET verify_token = NULL, email_verified = 1, user_start_date = NOW() 
                             WHERE verify_token = ?");
    $update->execute([$token]);

    if ($update->rowCount() > 0) {
        if (empty($user['user_end_date'])) {
            $updateEndDate = $con->prepare("UPDATE users 
                                            SET user_end_date = DATE_ADD(user_start_date, INTERVAL 3 DAY) 
                                            WHERE user_id = ?");
            $updateEndDate->execute([$user['user_id']]);
        }

        showMessage("✅ تم تفعيل حساب المستخدم بنجاح!", "#3498db");
    } else {
        showMessage("⚠️ الرابط مستخدم مسبقاً أو غير صالح.", "#f1c40f");
    }
}

// إذا لا بوس ولا يوزر
showMessage("❌ هذا الحساب غير موجود أو تم تفعيله مسبقًا.", "#e67e22");
