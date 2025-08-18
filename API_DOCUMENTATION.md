# التوثيق الشامل لواجهة برمجة التطبيقات الطبية

## 1. الهيكل الكامل للمشروع
### 1.1 المخطط التفصيلي للدلائل
```tree
├── app/
│   ├── admin/              # لوحة التحكم الإدارية
│   │   ├── boss/           # إدارة المدراء
│   │   ├── doctor/         # إدارة الأطباء
│   │   └── version/        # إصدارات النظام
│   ├── center/             # إدارة العيادة
│   │   ├── payments/       # المعاملات المالية
│   │   ├── sessions/       # الجلسات الطبية
│   │   └── treatment/      # العلاجات
│   ├── visitor/            # واجهة الزوار
│   └── exports/            # خدمات التصدير
├── auth/                   # أنظمة المصادقة
├── libs/
│   ├── PHPMailer/          # إرسال البريد الإلكتروني
│   ├── TCPDF-main/         # توليد التقارير PDF
│   └── php-jwt/            # إدارة التوكنات
├── uploads/                # الملفات المرفوعة
│   └── medicines/          # صور العلاجات
└── cron/                   # المهام المجدولة
```

## 2. تفاصيل الأمان المتقدمة
### 2.1 صلاحيات المستخدمين
```php
// auth/permissions.php
$roles = [
    'admin' => [
        'create_users',
        'delete_records',
        'view_financial_reports'
    ],
    'doctor' => [
        'manage_appointments',
        'prescribe_treatment',
        'view_patient_history'
    ],
    'secretary' => [
        'schedule_appointments',
        'update_patient_info'
    ]
];
```

## 3. المهام المجدولة (cron/)
### 3.1 تحديث حالة المواعيد
```php
// cron_update_status.php
$stmt = $con->prepare("
    UPDATE appointments 
    SET status = 'missed'
    WHERE appointment_date < CURDATE() 
    AND status = 'pending'
");
```

## 4. إدارة البريد الإلكتروني
```php
// libs/PHPMailer/PHPMailer.php
$mail->addAddress('patient@example.com', 'Patient Name');
$mail->Subject = 'تأكيد الموعد الطبي';
$mail->Body    = 'تم حجز موعدك بتاريخ ' . $appointmentDate;
$mail->send();
```

## 5. مخطط قاعدة البيانات
### 5.1 الجداول الرئيسية
```sql
-- appointments table
CREATE TABLE `appointments` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `patient_id` INT,
  `doctor_id` INT,
  `appointment_date` DATE,
  `status` ENUM('pending','completed','canceled','missed'),
  INDEX `doctor_date_idx` (`doctor_id`,`appointment_date`)
);

-- treatments table
CREATE TABLE `treatments` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `session_id` INT,
  `type` VARCHAR(50),
  `notes` TEXT,
  `prescription` TEXT,
  FOREIGN KEY (session_id) REFERENCES sessions(id)
);
```

## 6. إدارة الصور المتقدمة
### 6.1 سياسات التخزين
```php
// get-image.php
$maxFileSize = 5 * 1024 * 1024; // 5MB
$allowedMimeTypes = [
    'image/jpeg' => '.jpg',
    'image/png' => '.png',
    'application/pdf' => '.pdf'
];
```

## 7. حدود API ومعدل الطلبات
```ini
# .env
API_RATE_LIMIT=1000/hour
MAX_CONCURRENT_REQUESTS=50
REQUEST_TIMEOUT=30s
```

## 8. إرشادات النشر
### 8.1 متطلبات الخادم
```yaml
الحد الأدنى:
- PHP 8.1+
- MySQL 5.7+
- 2GB RAM
- 10GB Storage

الموصى به:
- PHP 8.2+ مع OPcache
- MySQL 8.0+ مع Replication
- 4GB RAM
- SSD Storage
```

## 9. سيناريوهات الاستخدام المتقدمة
### 9.1 دمج مع أنظمة خارجية
```php
// app/integrations/lab_system.php
$labResults = $externalLabAPI->getResults([
    'patient_national_id' => $nationalID,
    'from_date' => '2023-01-01'
]);
```

### 9.2 النسخ الاحتياطي التلقائي
```bash
# backup_script.sh
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > /backups/daily_backup_$(date +%F).sql
```

## 10. التسلسل الزمني للطلبات
```mermaid
sequenceDiagram
    participant Client
    participant API
    participant Database
    
    Client->>API: POST /appointments (JWT)
    API->>Database: التحقق من التوفر
    Database-->>API: نتيجة التوفر
    API->>Client: 201 Created
    API->>PHPMailer: إرسال تأكيد بالبريد