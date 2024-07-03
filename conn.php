<?php 

$host = "localhost";
$user = "getlearn_learner_user";
$pass = "db@password";
$db   = "getlearn_learner_db";
$conn = null;

try {
  $conn = new PDO("mysql:host={$host};dbname={$db};",$user,$pass);
} catch (Exception $e) {
  
}


 ?>