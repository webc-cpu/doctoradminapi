<?php
header("Content-Type: text/html; charset=UTF-8");

$token = "Mohamad.me15"; // توكن الحماية

// إذا ما أرسل التوكن أو التوكن غلط → عرض النموذج
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['token']) || $_POST['token'] !== $token) {
    ?>
    <!DOCTYPE html>
    <html lang="ar" dir="rtl">
    <head>
        <meta charset="UTF-8">
        <title>شجرة الملفات - إدخال التوكن</title>
        <style>
            body { font-family: 'Courier New', monospace; background: #f4f4f4; padding: 50px; text-align: center; }
            .box { background: white; padding: 30px; border-radius: 8px; display: inline-block; box-shadow: 0 0 10px #ccc; }
            input, button { padding: 10px; font-size: 16px; width: 280px; margin: 10px; }
        </style>
    </head>
    <body>
        <div class="box">
            <h3>🔐 الرجاء إدخال التوكن</h3>
            <form method="POST">
                <input type="text" name="token" placeholder="أدخل التوكن"><br>
                <button type="submit">عرض شجرة الملفات</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// الملفات والمجلدات المستبعدة
$excludeList = ['PHPMailer', 'TCPDF-main', 'trash', '.vscode', 'fonts', 'test', '.', '..'];

function printTree($dir, $prefix = "", $exclude = []) {
    $tree = "";
    $files = array_diff(scandir($dir), $exclude);
    $files = array_values($files);
    $count = count($files);

    foreach ($files as $index => $file) {
        $fullPath = "$dir/$file";
        $isDir = is_dir($fullPath);
        $isLast = ($index === $count - 1);
        $symbol = $isLast ? "┗" : "┣";
        $tree .= "$prefix $symbol $file\n";

        if ($isDir) {
            $newPrefix = $prefix . ($isLast ? "    " : "┃   ");
            $tree .= printTree($fullPath, $newPrefix, $exclude);
        }
    }

    return $tree;
}

// طباعة الشجرة النصية
$treeText = "doctoradminapi\n" . printTree(".", "", $excludeList);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>📁 شجرة ملفات المشروع</title>
    <style>
        body { font-family: 'Courier New', monospace; background: #f0f0f0; padding: 20px; }
        pre {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 8px #ccc;
            overflow-x: auto;
            white-space: pre;
        }
        button {
            padding: 10px 20px;
            margin-bottom: 10px;
            font-family: inherit;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <button onclick="copyTree()">📋 نسخ الشجرة</button>
    <pre id="tree"><?= htmlspecialchars($treeText) ?></pre>

    <script>
        function copyTree() {
            const text = document.getElementById('tree').innerText;
            navigator.clipboard.writeText(text).then(() => {
                alert("✅ تم نسخ الشجرة إلى الحافظة!");
            });
        }
    </script>
</body>
</html>
