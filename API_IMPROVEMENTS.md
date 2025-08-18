# مقترحات تحسين النظام

## 1. تحسينات الأمان
### 1.1 تحقق من صحة المدخلات بشكل أقوى
```php
// في ملفات مثل app/visitor/add.appointment.php
// بدلاً من:
$date = filterRequest("appointment_date");

// يُقترح:
$date = filter_var($_POST['appointment_date'], FILTER_VALIDATE_REGEXP, [
    'options' => [
        'regexp' => '/^20\d{2}-(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01])$/'
    ]
]);
```

### 1.2 إضافة معدل الطلبات (Rate Limiting)
```php
// في router.php
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);
$key = 'rate_limit:' . $_SERVER['REMOTE_ADDR'];

if ($redis->get($key) > 100) {
    http_response_code(429);
    die(json_encode(['error' => 'Too many requests']));
}
$redis->incr($key);
$redis->expire($key, 60);
```

## 2. تحسين الأداء
### 2.1 فهرسة قاعدة البيانات
```sql
-- في جدول appointments
ALTER TABLE appointments 
ADD INDEX idx_user_date (user_id, appointment_date);
```

### 2.2 التخزين المؤقت للاستعلامات
```php
// في app/visitor/view.php
$cacheKey = 'visitor_' . $id_visitor;
if ($data = apcu_fetch($cacheKey)) {
    echo $data;
    exit;
}
// ... استعلام قاعدة البيانات
apcu_store($cacheKey, json_encode($data), 3600);
```

## 3. تحسين البنية البرمجية
### 3.1 إنشاء كلاس موحد للردود
```php
// classes/Response.php
class APIResponse {
    public static function success($data) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'timestamp' => time(),
            'data' => $data
        ]);
    }
    
    public static function error($code, $message) {
        http_response_code($code);
        die(json_encode([
            'error' => [
                'code' => $code,
                'message' => $message
            ]
        ]));
    }
}

// الاستخدام في الملفات:
APIResponse::success($data);
APIResponse::error(400, "بيانات غير صالحة");
```

## 4. تحسين التوثيق
### 4.1 إضافة أمثلة تفاعلية
````markdown
```http
### إضافة موعد
POST /api/appointments
Authorization: Bearer {token}
Content-Type: application/json

{
  "patient_id": 123,
  "doctor_id": 456,
  "datetime": "2023-12-15 14:30"
}

### Response
{
  "status": "success",
  "appointment_id": 789,
  "confirmation_code": "AX9BZ"
}
```
````

## 5. إدارة الأخطاء المحسنة
### 5.1 تسجيل الأخطاء التفصيلي
```php
// في config.php
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    $logEntry = sprintf(
        "[%s] Error %s: %s in %s:%d\n",
        date('Y-m-d H:i:s'),
        $errno,
        $errstr,
        $errfile,
        $errline
    );
    
    file_put_contents(ROOT_PATH . 'logs/errors.log', $logEntry, FILE_APPEND);
    
    if ($_ENV['ENVIRONMENT'] === 'production') {
        error_log($logEntry, 3, ROOT_PATH . 'logs/prod_errors.log');
    }
});
```

## 6. تحسينات واجهة API
### 6.1 إضافة نظام الإصدارات
```php
// في router.php
$apiVersion = $_SERVER['HTTP_API_VERSION'] ?? 'v1';
$sanitizedPath = "api/$apiVersion/" . $sanitizedPath;
```

### 6.2 دعم التنسيقات المختلفة
```php
// في router.php
header('Content-Type: application/json'); // الافتراضي

if (isset($_SERVER['HTTP_ACCEPT'])) {
    if (strpos($_SERVER['HTTP_ACCEPT'], 'application/xml') !== false) {
        header('Content-Type: application/xml');
        // تحويل المخرجات لـ XML
    }
}
```

## 7. التحديثات المقترحة للمكتبات
| المكتبة      | الإصدار الحالي | الإصدار المقترح | التحسينات |
|-------------|----------------|------------------|-----------|
| TCPDF       | 6.4.4          | 6.6.0            | دعم أفضل للخطوط العربية |
| PHPMailer   | 6.5.3          | 6.8.0            | تحسينات أمان SMTP |
| php-jwt     | 5.4.0          | 6.0.0            | خوارزميات توقيع أحدث |

## 8. تحسينات واجهة الإدارة
### 8.1 إضافة نظام التحكم في الصلاحيات
```php
// في app/admin/permissions.php
$permissionMatrix = [
    'admin' => [
        'users' => ['create', 'read', 'update', 'delete'],
        'appointments' => ['full_access']
    ],
    'doctor' => [
        'appointments' => ['create', 'read'],
        'patients' => ['read']
    ]
];
```

## 9. نظام النسخ الاحتياطي
```php
// في cron/backup.php
$backupFile = ROOT_PATH . 'backups/db_backup_' . date('Y-m-d') . '.sql';
exec("mysqldump -u {$dbUser} -p{$dbPass} {$dbName} > {$backupFile}");
```

## 10. تحسينات قاعدة البيانات
### 10.1 تطبيع الجداول
```sql
-- فصل جدول المستخدمين عن بيانات العيادة
CREATE TABLE clinic_profiles (
    clinic_id INT PRIMARY KEY,
    user_id INT,
    clinic_name VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

///////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////

