<?php
include("../../conn.php");
extract($_POST);

// Hash the password
$hashedPassword = password_hash($exPass, PASSWORD_DEFAULT);

$updCourse = $conn->query("UPDATE examinee_tbl SET exmne_fullname='$exFullname', exmne_email='$exEmail', exmne_password='$hashedPassword' WHERE exmne_id='$exmne_id' ");

if($updCourse) {
    $res = array("res" => "success", "exFullname" => $exFullname);
} else {
    $res = array("res" => "failed");
}

echo json_encode($res);
?>
