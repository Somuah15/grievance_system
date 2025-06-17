<?php
$password = 'admin123'; // Replace with your desired admin password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
echo $hashed_password;
?>
