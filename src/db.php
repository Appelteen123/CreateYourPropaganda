<?php
$host = 'localhost';
$db   = 'fotoforum';
$user = 'root';
$pass = 'hallo123'; // Jouw wachtwoord

$pdo = new PDO("mysql:host=$host;port=3307;dbname=$db;charset=utf8mb4", $user, $pass);
session_start();
?>