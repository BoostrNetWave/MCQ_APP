<?php 

// Count All Exam
$selExam = $conn->query("SELECT COUNT(ex_id) as totExam FROM exam_tbl ")->fetch(PDO::FETCH_ASSOC);


//Count All Examinee 
$selExaminee = $conn->query("SELECT COUNT(exmne_id) as totExmne FROM examinee_tbl ")->fetch(PDO::FETCH_ASSOC);

//Total All Feedback
$selFeedback = $conn->query("SELECT COUNT(fb_id) as totFeedback FROM feedbacks_tbl ")->fetch(PDO::FETCH_ASSOC);

//Total All Question

$selQuesion = $conn->query("SELECT COUNT(eqt_id) as totQuestion FROM exam_question_tbl ")->fetch(PDO::FETCH_ASSOC);


 ?>
