<?php
header("Content-Type: application/json");
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

include('../conn.php'); // Adjust the path to your connection script if needed
session_start();

$requestMethod = $_SERVER['REQUEST_METHOD'];

if ($requestMethod == "GET") {
    if (!isset($_GET['user_id'])) {
        echo json_encode(['response_code' => 1, 'error' => 'user_id is required']);
        http_response_code(400);
        exit();
    }

    $userId = $_GET['user_id'];

    try {
        $stmt = $conn->prepare("
            SELECT withdrawal_id AS withdraw_id ,amount,status,withdrawal_date AS withdraw_date FROM withdrawals WHERE user_id=:user_id ORDER BY withdraw_date DESC
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
        $stmt->execute();
        $reportedQuestions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($reportedQuestions)) {
            echo json_encode(['response_code' => 0, 'message' => 'No Withdrawal History Found']);
        } else {
            echo json_encode(['response_code' => 0, 'withdraw_history' => $reportedQuestions]);
        }
        http_response_code(200);

    } catch (PDOException $pdoException) {
        echo json_encode(['response_code' => 1, 'error' => 'PDO Error: ' . $pdoException->getMessage()]);
        http_response_code(500);
    }
} else {
    echo json_encode(['response_code' => 1, 'error' => 'Invalid request method']);
    http_response_code(405);
}
?>
