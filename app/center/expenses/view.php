<?php
include ROOT_PATH . 'connect.php'; // الاتصال وقيم filterRequest








// شرح حالات واجهة برمجة التطبيقات (API)
// جلب مصاريف مستخدم عادي:

// ...................1.....................
// ما يتم إرساله: رقم المستخدم (user_id)

// ما يتم إرجاعه: جميع المصاريف الخاصة بالمستخدم فقط مع حالة نجاح، والبيانات تكون داخل قسم البيانات.

// ..................................2........................
// جلب مصاريف سكرتيرة:

// ما يتم إرساله: رقم السكرتيرة (secretary_id)

// ما يتم إرجاعه: جميع المصاريف الخاصة بالسكرتيرة فقط مع حالة نجاح، والبيانات تكون داخل قسم البيانات.
// .................................1....................
// جلب مصاريف مدير (بوس):

// ما يتم إرساله: رقم المدير (id_boss)

// ما يتم إرجاعه: جميع المصاريف التي أضافها المدير نفسه، بالإضافة إلى جميع المصاريف التي أضافتها السكرتيرات التابعات له، مع حالة نجاح، والبيانات داخل قسم البيانات.







// استقبال المعرفات
$user_id      = filterRequest("user_id");
$secretary_id = filterRequest("secretary_id");
$id_boss      = filterRequest("id_boss");

if (!empty($user_id)) {
    // عرض مصاريف يوزر محدد
    $stmt = $con->prepare("SELECT * FROM expenses WHERE user_id = ?");
    $stmt->execute([$user_id]);

} elseif (!empty($secretary_id)) {
    // عرض مصاريف السكرتيرة فقط
    $stmt = $con->prepare("SELECT * FROM expenses WHERE secretary_id = ?");
    $stmt->execute([$secretary_id]);

} elseif (!empty($id_boss)) {
    // عرض مصاريف البوس نفسه + السكرتيرات التابعة إله
    // أولاً نجلب جميع id السكرتيرات التابعة للبوس
    $stmt = $con->prepare("
        SELECT * FROM expenses
        WHERE id_boss = :id_boss
        OR secretary_id IN (
            SELECT secretary_id FROM secretaries WHERE boss_secretary = :id_boss
        )
    ");
    $stmt->execute(['id_boss' => $id_boss]);

} else {
    // عرض كل المصاريف (اختياري)
    $stmt = $con->prepare("SELECT * FROM expenses");
    $stmt->execute();
}

// جلب البيانات
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
$count = $stmt->rowCount();

if ($count > 0) {
    echo json_encode(["status" => "success", "data" => $data]);
} else {
    echo json_encode(["status" => "fail", "message" => "لا توجد بيانات مصاريف"]);
}
