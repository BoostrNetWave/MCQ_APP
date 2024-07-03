<?php
// Include your database connection script
include("../../conn.php");

// Check if the request method is POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if 'withdrawal_id' is set in POST data
    if (isset($_POST['withdrawal_id'])) {
        $withdrawal_id = $_POST['withdrawal_id'];
        
        try {
            // Prepare and execute the SQL update statement
            $updateStmt = $conn->prepare("UPDATE withdrawals SET status = 'Payment Done' WHERE withdrawal_id = :withdrawal_id");
            $updateStmt->bindParam(':withdrawal_id', $withdrawal_id);
            $updateStmt->execute();
            
            // Check if the update was successful
            if ($updateStmt->rowCount() > 0) {
                // Return a JSON response indicating success
                $response = array('status' => 'success', 'message' => 'Withdrawal status updated successfully');
                echo json_encode($response);
            } else {
                // Return a JSON response indicating failure
                $response = array('status' => 'error', 'message' => 'No rows updated. Check if withdrawal_id exists or status is already "Payment Done".');
                echo json_encode($response);
            }
        } catch(PDOException $e) {
            // Return a JSON response for any database errors
            $response = array('status' => 'error', 'message' => 'Error updating withdrawal status: ' . $e->getMessage());
            echo json_encode($response);
        }
    } else {
        // Return a JSON response if 'withdrawal_id' is not set
        $response = array('status' => 'error', 'message' => 'No withdrawal_id parameter received.');
        echo json_encode($response);
    }
} else {
    // Return a JSON response if request method is not POST
    $response = array('status' => 'error', 'message' => 'Invalid request method.');
    echo json_encode($response);
}
?>
