<?php 
 include("../../conn.php");
 
 extract($_POST);


 $updExam = $conn->query("UPDATE exam_tbl SET  ex_title='$examTitle', ex_description='$examDesc' WHERE  ex_id='$examId' ");

 if($updExam)
 {
   $res = array("res" => "success", "msg" => $examTitle);
 }
 else
 {
   $res = array("res" => "failed");
 }

 echo json_encode($res);
 ?>