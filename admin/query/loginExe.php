<?php
session_start();
include("../../conn.php");

extract($_POST);

$selAcc = $conn->prepare("SELECT * FROM admin_acc WHERE admin_user = :username");
$selAcc->bindParam(':username', $username);
$selAcc->execute();
$selAccRow = $selAcc->fetch(PDO::FETCH_ASSOC);

if ($selAccRow && password_verify($pass, $selAccRow['admin_pass'])) {
    $_SESSION['admin'] = array(
        'admin_id' => $selAccRow['admin_id'],
        'adminnakalogin' => true
    );
    $res = array("res" => "success");
} else {
    $res = array("res" => "invalid");
}

echo json_encode($res);
?>
