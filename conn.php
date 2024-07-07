<?php 

$host = "localhost";
$user = "mcq-app";
$pass = "sivA898S";
$db   = "learners_db";
$conn = null;

try {
  $conn = new PDO("mysql:host={$host};dbname={$db};",$user,$pass);
} catch (Exception $e) {
  
}


 ?>