<?php
// هذا المسار هو جذر المشروع (doctoradminapi)
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . '/'); 
}

// مسار مكتبة TCPDF داخل جذر المشروع
if (!defined('TCPDF_PATH')) {
    define('TCPDF_PATH', ROOT_PATH . 'TCPDF-main/tcpdf.php');
}

// ممكن تضيف مسارات لمكتبات أو ملفات ثانية هنا
