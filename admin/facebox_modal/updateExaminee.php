
<?php 
  include("../../conn.php");
  $id = $_GET['id'];
 
  $selExmne = $conn->query("SELECT * FROM examinee_tbl WHERE exmne_id='$id' ")->fetch(PDO::FETCH_ASSOC);

 ?>

<fieldset style="width:543px;" >
	<legend><i class="facebox-header"><i class="edit large icon"></i>&nbsp;Update <b>( <?php echo strtoupper($selExmne['exmne_fullname']); ?> )</b></i></legend>
  <div class="col-md-12 mt-4">
<form method="post" id="updateExamineeFrm">
     <div class="form-group">
        <legend>Fullname</legend>
        <input type="hidden" name="exmne_id" value="<?php echo $id; ?>">
        <input type="" name="exFullname" class="form-control" required="" value="<?php echo $selExmne['exmne_fullname']; ?>" >
     </div>
     <div class="form-group">
        <legend>Email</legend>
        <input type="" name="exEmail" class="form-control" required="" value="<?php echo $selExmne['exmne_email']; ?>" >
     </div>

     <div class="form-group">
        <legend>Password</legend>
        <input type="" name="exPass" class="form-control" required="" value="<?php echo $selExmne['exmne_password']; ?>" >
     </div>

     <div class="form-group">
        <legend>Status</legend>
        <input type="hidden" name="course_id" value="<?php echo $id; ?>">
        <input type="" name="newCourseName" class="form-control" required="" value="<?php echo $selExmne['exmne_status']; ?>" >
     </div>
  <div class="form-group" align="right">
    <button type="submit" class="btn btn-sm btn-primary">Update Now</button>
  </div>
</form>
  </div>
</fieldset>







