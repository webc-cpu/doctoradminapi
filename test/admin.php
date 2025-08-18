
<!-- كود لتشفير كلمة المرور -->

<?php
$password = "Mohamad.me15"; // ← غيّرها لكلمة مرورك
$hashed = password_hash($password, PASSWORD_DEFAULT);
echo $hashed;
