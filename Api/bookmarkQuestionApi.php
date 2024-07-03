<?php
header("Content-Type: application/json");
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE'); // Allow POST and DELETE methods
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

include('../conn.php');
session_start();

$requestMethod = $_SERVER['REQUEST_METHOD'];

if ($requestMethod == "POST") {
    // Handling POST request to create a bookmark

    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['user_id']) || !isset($input['question_id'])) {
        echo json_encode(['response_code' => 1, 'error' => 'user_id and question_id are required']);
        http_response_code(400);
        exit();
    }

    $userId = $input['user_id'];
    $questionId = $input['question_id'];

    try {
        // Check if the question is already bookmarked
        $stmt = $conn->prepare("SELECT * FROM bookmarks WHERE user_id = :user_id AND question_id = :question_id");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
        $stmt->bindValue(':question_id', $questionId, PDO::PARAM_STR);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['response_code' => 1, 'error' => 'Question already bookmarked']);
            http_response_code(400);
            exit();
        }

        // Insert the bookmark
        $stmt = $conn->prepare("INSERT INTO bookmarks (user_id, question_id) VALUES (:user_id, :question_id)");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
        $stmt->bindValue(':question_id', $questionId, PDO::PARAM_STR);
        $stmt->execute();

        echo json_encode(['response_code' => 0, 'message' => 'Question bookmarked successfully']);
        http_response_code(200);

    } catch (PDOException $pdoException) {
        echo json_encode(['response_code' => 1, 'error' => 'PDO Error: ' . $pdoException->getMessage()]);
        http_response_code(500);
    }
} else if ($requestMethod == "DELETE") {
    // Handling DELETE request to delete a bookmark

    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['user_id']) || !isset($input['question_id'])) {
        echo json_encode(['response_code' => 1, 'error' => 'user_id and question_id are required']);
        http_response_code(400);
        exit();
    }

    $userId = $input['user_id'];
    $questionId = $input['question_id'];

    try {
        // Check if the question is bookmarked
        $stmt = $conn->prepare("SELECT * FROM bookmarks WHERE user_id = :user_id AND question_id = :question_id");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
        $stmt->bindValue(':question_id', $questionId, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->rowCount() == 0) {
            echo json_encode(['response_code' => 1, 'error' => 'Question not bookmarked']);
            http_response_code(400);
            exit();
        }

        // Delete the bookmark
        $stmt = $conn->prepare("DELETE FROM bookmarks WHERE user_id = :user_id AND question_id = :question_id");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
        $stmt->bindValue(':question_id', $questionId, PDO::PARAM_STR);
        $stmt->execute();

        echo json_encode(['response_code' => 0, 'message' => 'Bookmark deleted successfully']);
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
