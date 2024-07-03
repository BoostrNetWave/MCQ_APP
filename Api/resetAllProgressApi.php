<?php
header("Content-Type: application/json");
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

include("../conn.php");

$requestMethod = $_SERVER['REQUEST_METHOD'];

if ($requestMethod === "POST") {
    $input = json_decode(file_get_contents("php://input"), true);

    if (!isset($input['user_id'])) {
        http_response_code(400);
        echo json_encode(['response_code' => 1, 'error' => 'user_id is required']);
        exit();
    }

    $userId = $input['user_id'];

    try {
        // Begin transaction for atomic operations
        $conn->beginTransaction();

        // Delete from multiple tables in one transaction
        $deleteQueries = [
            "DELETE FROM user_scores WHERE user_id = :user_id",
            "DELETE FROM points WHERE user_id = :user_id",
            "DELETE FROM level_tbl WHERE user_id = :user_id",
            "DELETE FROM bookmarks WHERE user_id = :user_id",
            "DELETE FROM game_answers WHERE user_id = :user_id",
            "DELETE FROM game_sessions WHERE winner_id = :user_id",
            "DELETE FROM reported_questions WHERE user_id = :user_id",
            "DELETE FROM reward_tbl WHERE user_id = :user_id",
            "DELETE FROM user_attempts WHERE user_id = :user_id"
           
        ];

        foreach ($deleteQueries as $query) {
            $stmt = $conn->prepare($query);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
            $stmt->execute();
        }

        // Commit the transaction
        $conn->commit();

        http_response_code(200);
        echo json_encode(['response_code' => 0, 'message' => 'Reset Done successfully']);
    } catch (PDOException $pdoException) {
        $conn->rollBack();
        http_response_code(500);
        echo json_encode(['response_code' => 1, 'error' => 'Database error: ' . $pdoException->getMessage()]);
    } catch (Exception $exception) {
        $conn->rollBack();
        http_response_code(500);
        echo json_encode(['response_code' => 1, 'error' => 'Unexpected error: ' . $exception->getMessage()]);
    }
} else {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['response_code' => 1, 'error' => 'Invalid request method']);
}
?>
