<?php
$password = 'password123';
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "Password: {$password}<br>";
echo "Hash: {$hash}<br><br>";
echo "Copy this hash and use it in phpMyAdmin INSERT statement.";
?>