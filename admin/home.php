<?php 
session_start();

if(!isset($_SESSION['admin']['adminnakalogin']) == true) header("location:index.php");


 ?>
<?php include("../conn.php"); ?>

<?php include("includes/header.php"); ?>      

<div class="app-main">

<?php include("includes/sidebar.php"); ?>


<?php 
   @$page = $_GET['page'];


   if($page != '')
   {
      if($page == "manage-exam")
     {
      include("pages/manage-exam.php");
     }
     else if($page == "leaderboard")
     {
      include("pages/Leaderboard.php");
     }
     else if($page == "feedbacks")
     {
      include("pages/feedbacks.php");
     }
     else if($page == "User_Withdraw")
     {
      include("pages/User_Withdraw.php");
     }

       
   }

   else
   {
     include("pages/home.php"); 
   }


 ?> 


<?php include("includes/footer.php"); ?>

<?php include("includes/modals.php"); ?>
