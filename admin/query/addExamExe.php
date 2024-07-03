<?php 
 include("../../conn.php");

 extract($_POST);

 $selCourse = $conn->query("SELECT * FROM exam_tbl WHERE ex_title='$examTitle' ");

  if($selCourse->rowCount() > 0)
 {
	$res = array("res" => "exist", "examTitle" => $examTitle);
 }
 else
 {
    
	$insExam = $conn->query("INSERT INTO exam_tbl(ex_title,ex_description) VALUES('$examTitle','$examDesc') ");
	if($insExam)
	{
		$res = array("res" => "success", "examTitle" => $examTitle);
	}
	else
	{
		$res = array("res" => "failed", "examTitle" => $examTitle);
	}


 }




 echo json_encode($res);
 ?>