<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

include ROOT_PATH . 'connect.php';

$patient_id = filterRequest("treatment_id");

$stmt = $con->prepare("SELECT * FROM treatment_images WHERE treatment_id = ?");
$stmt->execute(array($patient_id));

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
$count = $stmt->rowCount();

if ($count > 0) {
    echo json_encode(array("status" => "success", "data" => $data));
} else {
    echo json_encode(array("status" => "fail"));
}
?>
هاد مع هاد
<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8" />
  <title>عرض صور العلاج</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      direction: rtl;
      padding: 20px;
    }
    label, input, button {
      font-size: 16px;
      margin: 5px 0;
    }
    #images {
      margin-top: 20px;
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
    }
    #images img {
      max-width: 150px;
      max-height: 150px;
      border: 1px solid #ddd;
      border-radius: 6px;
    }
  </style>
</head>
<body>

  <h2>أدخل معرف العلاج لعرض الصور</h2>

  <form id="treatmentForm">
    <label for="treatment_id">معرف العلاج (treatment_id):</label><br />
    <input type="number" id="treatment_id" name="treatment_id" required />
    <br />
    <button type="submit">عرض الصور</button>
  </form>

  <div id="images"></div>

  <script>
    const form = document.getElementById('treatmentForm');
    const imagesDiv = document.getElementById('images');

    // غيرها حسب دومينك الحقيقي (مع / في النهاية)
    const baseUrl = 'http://we-bc.atwebpages.com/doctoradminapi/';

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      imagesDiv.innerHTML = 'جاري التحميل...';

      const treatmentId = document.getElementById('treatment_id').value;

      fetch('http://we-bc.atwebpages.com/doctoradminapi/treatment/view_imeg.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: treatment_id=${encodeURIComponent(treatmentId)},
      })
        .then(response => response.json())
        .then(data => {
          console.log(data);
          imagesDiv.innerHTML = '';

          if (data.status === 'success' && data.data.length > 0) {
            data.data.forEach(item => {
              const img = document.createElement('img');
              img.src = baseUrl + item.image_path;
              img.alt = 'صورة العلاج';
              imagesDiv.appendChild(img);
            });
          } else {
            imagesDiv.textContent = 'لا توجد صور لهذا المعرف.';
          }
        })
        .catch(error => {
          imagesDiv.textContent = 'حدث خطأ أثناء جلب الصور.';
          console.error(error);
        });
    });
  </script>
</body>
</html>
عم تنعرض الصورة ليش