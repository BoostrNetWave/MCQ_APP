<?php

header("Content-Type: application/json");
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

include('../conn.php'); // Adjust the path as per your file structure
session_start();

$requestMethod = $_SERVER['REQUEST_METHOD'];

if ($requestMethod == "POST") {
    $input = json_decode(file_get_contents('php://input'), true);

    // Validate input
    if (!isset($input['user_id'])) {
        http_response_code(400);
        echo json_encode(['response_code' => 1, 'error' => 'user_id is required']);
        exit();
    }

    $userId = $input['user_id'];

    // Check if the user exists in the database
    $checkUserQuery = $conn->prepare("SELECT * FROM examinee_tbl WHERE exmne_id = :user_id");
    $checkUserQuery->bindValue(':user_id', $userId, PDO::PARAM_STR);
    $checkUserQuery->execute();
    $userExists = $checkUserQuery->fetch(PDO::FETCH_ASSOC);

    if (!$userExists) {
        http_response_code(404);
        echo json_encode(['response_code' => 1, 'error' => 'Invalid user id']);
        exit();
    }

    // Check if there is an active game session for the user waiting to be joined
    $sql = "SELECT game_id, start_time FROM game_sessions WHERE status = 'waiting' AND user2_id IS NULL AND user1_id != :user_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
    $stmt->execute();
    $activeGame = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($activeGame) {
        // Join the existing game session
        $gameId = $activeGame['game_id'];
        $updateSql = "UPDATE game_sessions SET user2_id = :user_id, status = 'active' WHERE game_id = :game_id";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
        $updateStmt->bindValue(':game_id', $gameId, PDO::PARAM_STR);
        $updateStmt->execute();

        if ($updateStmt->rowCount() > 0) {
            // Proceed to insert questions into game_question table
            insertQuestionsIntoGame($conn, $gameId);

            echo json_encode([
                'response_code' => 0,
                'message' => 'User added to the game session.You can play now.',
                'game_id' => $gameId
            ]);
            http_response_code(200);
        } else {
            http_response_code(500);
            echo json_encode(['response_code' => 1, 'error' => 'Failed to join game session']);
        }

    } else {
        // Create a new game session
        $gameId = uniqid();
         $startTime = date('Y-m-d H:i:s');
        try {
            $conn->beginTransaction();

            // Insert game session into database
            $insertSql = "INSERT INTO game_sessions (game_id, user1_id, status, start_time,game_start_time) VALUES (:game_id, :user_id, 'waiting', NOW(),:start_time)";
            $insertStmt = $conn->prepare($insertSql);
            $insertStmt->bindValue(':game_id', $gameId, PDO::PARAM_STR);
            $insertStmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
            $insertStmt->bindValue(':start_time', $startTime, PDO::PARAM_STR);
            $insertStmt->execute();

            if ($insertStmt->rowCount() > 0) {
                // Commit transaction if successful
                $conn->commit();

                // Immediate response that the user has been added to the waiting list
                echo json_encode([
                    'response_code' => 0,
                    'message' => 'User added to a game session and is waiting for another player',
                    'game_id' => $gameId
                ]);
                http_response_code(200);

                // Execute background process
                exec("php check_user_2.php $gameId > /dev/null &");

            } else {
                // Rollback transaction if insertion failed
                $conn->rollBack();
                http_response_code(500);
                echo json_encode(['response_code' => 1, 'error' => 'Failed to create game session']);
            }

        } catch (PDOException $e) {
            $conn->rollBack();
            http_response_code(500);
            echo json_encode(['response_code' => 1, 'error' => 'Database error: ' . $e->getMessage()]);
        }
    }

} else {
    http_response_code(405);
    echo json_encode(['response_code' => 1, 'error' => 'Invalid request method']);
}

function insertQuestionsIntoGame($conn, $gameId) {
    // Select random questions for the game
    $stmt = $conn->prepare("SELECT eqt_id, exam_question, exam_answer, exam_ch1, exam_ch2, exam_ch3, exam_ch4 FROM exam_question_tbl ORDER BY RAND() LIMIT 10");
    $stmt->execute();
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Store questions in session for the game
    $_SESSION['game_questions'][$gameId] = $questions;

    // Prepare questions in the desired JSON response format
    $formattedQuestions = [];
    foreach ($questions as $question) {
        $options = [
            $question['exam_answer'],
            $question['exam_ch1'],
            $question['exam_ch2'],
            $question['exam_ch3'],
            $question['exam_ch4']
        ];
        shuffle($options);

        $formattedQuestions[] = [
            'question_id' => $question['eqt_id'],
            'question' => $question['exam_question'],
            'correct_answer' => $question['exam_answer'],
            'incorrect_answers' => array_values(array_diff($options, [$question['exam_answer']]))
        ];

        // Insert each question into game_question table
        $insertQuestion = $conn->prepare("
            INSERT INTO game_question (game_id, question_id, question, correct_answer, timestamp)
            VALUES (:game_id, :question_id, :question, :correct_answer, :timestamp)
        ");
        $insertQuestion->bindValue(':game_id', $gameId, PDO::PARAM_STR);
        $insertQuestion->bindValue(':question_id', $question['eqt_id'], PDO::PARAM_STR); // Treat question_id as string
        $insertQuestion->bindValue(':question', $question['exam_question'], PDO::PARAM_STR);
        $insertQuestion->bindValue(':correct_answer', $question['exam_answer'], PDO::PARAM_STR);
        $insertQuestion->bindValue(':timestamp', date('Y-m-d H:i:s'), PDO::PARAM_STR); // Use current timestamp
        $insertQuestion->execute();

        // Debugging: Check for errors during insert
        if ($insertQuestion->rowCount() == 0) {
            throw new Exception('Failed to insert question into game_question table');
        }
    }
}

?>
