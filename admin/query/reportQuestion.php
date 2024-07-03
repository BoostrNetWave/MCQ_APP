<?php
 include("../../conn.php");

if (isset($_POST['question_id'])) {
    $questionId = $_POST['question_id'];

    try {
        $updateQuery = $conn->prepare("UPDATE reported_questions SET status = 'issue resolved' WHERE question_id = :questionId");
        $updateQuery->bindParam(':questionId', $questionId, PDO::PARAM_INT);
        $updateQuery->execute();
        echo "Issue resolved successfully";
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "Invalid request";
}
?>
