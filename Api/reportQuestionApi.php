<?php
header("Content-Type: application/json");
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

include('../conn.php');
session_start();

$requestMethod = $_SERVER['REQUEST_METHOD'];

if ($requestMethod == "POST") {
    $input = json_decode(file_get_contents('php://input'), true);

    // Validate input
    if (!isset($input['user_id']) || !isset($input['question_id']) || !isset($input['report_reason'])) {
        echo json_encode(['response_code' => 1, 'error' => 'user_id, question_id, and report_reason are required']);
        http_response_code(400);
        exit();
    }

    $userId = $input['user_id'];
    $questionId = $input['question_id'];
    $reportReason = $input['report_reason'];

    try {
        // Insert reported question into reported_questions table
        $stmt = $conn->prepare("
            INSERT INTO reported_questions (user_id, question_id, report_reason)
            VALUES (:user_id, :question_id, :report_reason)
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
        $stmt->bindValue(':question_id', $questionId, PDO::PARAM_STR);
        $stmt->bindValue(':report_reason', $reportReason, PDO::PARAM_STR);
        $stmt->execute();

        echo json_encode(['response_code' => 0, 'message' => 'Question reported successfully']);
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
