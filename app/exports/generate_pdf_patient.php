<?php
include ROOT_PATH . 'connect.php'; // ربط الاتصال

require_once ROOT_PATH . 'libs/TCPDF-main/tcpdf.php'; // هذا المسار معرف في config.php


$patient_id = $_POST['patient_id'] ?? null;
if (!$patient_id) {
    die("لم يتم إرسال رقم تعريف المريض.");
}

$stmt = $con->prepare("SELECT * FROM patients WHERE patient_id = ?");
$stmt->execute([$patient_id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    die("المريض غير موجود.");
}

$stmt = $con->prepare("SELECT * FROM sessions WHERE patient_id = ?");
$stmt->execute([$patient_id]);
$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('تطبيق طبي');
$pdf->SetTitle("تقرير المريض: " . strip_tags($patient['patient_name']));
$pdf->SetSubject('تقرير طبي');
$pdf->SetMargins(10, 15, 10);
$pdf->SetAutoPageBreak(TRUE, 15);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->setRTL(true);

$fontPath = __DIR__ . '/../../fonts/Amiri-Regular.ttf';
$fontname = TCPDF_FONTS::addTTFfont($fontPath, 'TrueTypeUnicode', '', 96);

$pdf->AddPage();

$html = '
<h2 style="text-align:center; font-family: Amiri, serif; color:#000; margin-bottom:12px;">استمارة بيانات المريض</h2>
<div style="margin: 0 auto; width: 95%;">
<table cellpadding="6" cellspacing="0" border="0" width="100%" dir="rtl" style="font-family: Amiri, serif; font-size: 13px; border:1px solid #ddd; border-radius:8px; background:#fff; color:#000;">
  <tr style="background:#F9F9F9;">
    <td width="33%"><strong>الاسم:</strong> ' . htmlspecialchars($patient['patient_name']) . '</td>
    <td width="33%"><strong>رقم الهاتف:</strong> ' . htmlspecialchars($patient['Phone_Number']) . '</td>
    <td width="33%"><strong>العمر:</strong> ' . htmlspecialchars($patient['Age']) . '</td>
  </tr>
  <tr>
    <td><strong>الجنس:</strong> ' . htmlspecialchars($patient['Gender']) . '</td>
    <td><strong>الحساسية من الأدوية:</strong> ' . htmlspecialchars($patient['Drug_Allergies']) . '</td>
    <td><strong>العنوان:</strong> ' . htmlspecialchars($patient['Address']) . '</td>
  </tr>
  <tr style="background:#F9F9F9;">
    <td><strong>حامل:</strong> ' . htmlspecialchars($patient['Pregnant']) . '</td>
    <td><strong>مدخن:</strong> ' . htmlspecialchars($patient['Smoker']) . '</td>
    <td><strong>رقم البطاقة الطبية:</strong> ' . htmlspecialchars($patient['patient_card']) . '</td>
  </tr>
  <tr>
    <td><strong>تاريخ التسجيل:</strong> ' . htmlspecialchars($patient['patient_date']) . '</td>
    <td><strong>المدفوع:</strong> ' . htmlspecialchars($patient['patient_total_paymensts']) . '</td>
    <td><strong>الإجمالي:</strong> ' . htmlspecialchars($patient['patient_total_total']) . '</td>
  </tr>
  <tr style="background:#F9F9F9;">
    <td colspan="3"><strong>الباقي:</strong> ' . htmlspecialchars($patient['patient_the_rest']) . '</td>
  </tr>
</table>
</div>

<hr style="margin:18px auto 18px auto; width: 95%; border: 1px solid #ccc;">
<h3 style="text-align:right; font-family: Amiri, serif; color:#000; margin-bottom: 12px;">الجلسات والعلاجات</h3>
';

foreach ($sessions as $session) {
    $html .= '<div style="margin-bottom:15px; width:95%; margin-left:auto; margin-right:auto; font-family: Amiri, serif; color:#000;">';
    $html .= '<p style="background:#E8F0FE; padding:8px; border-radius:6px; font-size:14px; font-weight:bold; color:#000;">
                اسم الجلسة: ' . htmlspecialchars($session['session_name']) . ' | 
                تاريخ الجلسة: ' . htmlspecialchars($session['session_date']) . '
              </p>';
    $html .= '<p style="margin-top:4px; margin-bottom:10px; font-size:13px; color:#000;">
                <strong>ملاحظة الجلسة:</strong> ' . nl2br(htmlspecialchars($session['session_note'])) . '
              </p>';

    $stmt = $con->prepare("SELECT * FROM treatment WHERE session_id = ?");
    $stmt->execute([$session['session_id']]);
    $treatments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($treatments) > 0) {
        $html .= '<div style="margin: 0 auto; width: 95%;">';
        $html .= '<table cellpadding="6" cellspacing="0" border="1" width="100%" dir="rtl" style="border-collapse:collapse; font-family: Amiri, serif; font-size: 12px; color:#000;">
        <tr style="background:#fff; color:#000; font-weight:bold; font-size:14px; text-align:center;">
            <td style="width: 18%; padding:6px;">اسم العلاج</td>
            <td style="width: 10%; padding:6px;">رقم العلاج</td>
            <td style="width: 12%; padding:6px;">بطاقة العلاج</td>
            <td style="width: 12%; padding:6px;">تاريخ العلاج</td>
            <td style="width: 10%; padding:6px;">المدفوع</td>
            <td style="width: 10%; padding:6px;">الإجمالي</td>
            <td style="width: 28%; padding:6px;">ملاحظات العلاج</td>
        </tr>';

        foreach ($treatments as $treatment) {
            $html .= '<tr style="text-align:center; color:#000;">
                        <td style="text-align:right; padding-right:6px;">' . htmlspecialchars($treatment['treatment_name']) . '</td>
                        <td>' . htmlspecialchars($treatment['treatment_number']) . '</td>
                        <td>' . htmlspecialchars($treatment['treatment_card']) . '</td>
                        <td>' . htmlspecialchars($treatment['treatment_date']) . '</td>
                        <td>' . htmlspecialchars($treatment['treatment_payment']) . '</td>
                        <td>' . htmlspecialchars($treatment['treatment_total']) . '</td>
                        <td style="text-align:right; padding-right:6px;">' . nl2br(htmlspecialchars($treatment['treatment_note'])) . '</td>
                      </tr>';
        }
        $html .= '</table>';
        $html .= '</div>';
    } else {
        $html .= '<p style="font-style:italic; color:#666;">لا توجد علاجات لهذه الجلسة.</p>';
    }
    $html .= '</div><hr style="margin:20px auto; border: 1px solid #ccc; width: 95%;">';
}

$pdf->writeHTML($html, true, false, true, false, '');
$clean_name = preg_replace('/[^\p{Arabic}\w\-]/u', '_', $patient['patient_name']);
$pdf->Output("تقرير_المريض_{$clean_name}.pdf", 'I');

exit;
