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
    if (!isset($input['game_id']) || !isset($input['user_id']) || !isset($input['answers']) || !is_array($input['answers'])) {
        echo json_encode(['response_code' => 1, 'error' => 'game_id, user_id, and answers (array) are required']);
        http_response_code(400);
        exit();
    }

    $gameId = $input['game_id'];
    $userId = $input['user_id'];
    $answers = $input['answers'];

    try {
        // Set error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Check if the game session exists and is active
        $stmt = $conn->prepare("SELECT user1_id, user2_id, game_start_time FROM game_sessions WHERE game_id = :game_id AND status = 'active'");
        $stmt->bindValue(':game_id', $gameId, PDO::PARAM_STR);
        $stmt->execute();
        $gameSession = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$gameSession) {
            echo json_encode(['response_code' => 1, 'error' => 'Game session not found or not active']);
            http_response_code(404);
            exit();
        }

        $user1Id = $gameSession['user1_id'];
        $user2Id = $gameSession['user2_id'];
        $startTime=strtotime($gameSession['game_start_time']);
        $currentTime = time();
    

        // Validate if user is part of the game session
        if ($userId != $user1Id && $userId != $user2Id) {
            echo json_encode(['response_code' => 1, 'error' => 'User is not part of this game session']);
            http_response_code(403);
            exit();
        }

        // Check if the user has already submitted answers for this game session
        $stmt = $conn->prepare("SELECT COUNT(*) FROM game_answers WHERE game_id = :game_id AND user_id = :user_id");
        $stmt->bindValue(':game_id', $gameId, PDO::PARAM_STR);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_STR); // Treat user_id as string
        $stmt->execute();
        $answersCount = $stmt->fetchColumn();

        if ($answersCount > 0) {
            echo json_encode(['response_code' => 1, 'error' => 'User has already submitted answers for this game session']);
            http_response_code(400);
            exit();
        }

        // Fetch all questions for the game session
        $stmt = $conn->prepare("SELECT question_id, correct_answer FROM game_question WHERE game_id = :game_id ORDER BY question_id ASC");
        $stmt->bindValue(':game_id', $gameId, PDO::PARAM_STR);
        $stmt->execute();
        $gameQuestions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$gameQuestions) {
            echo json_encode(['response_code' => 1, 'error' => 'No questions found for this game session']);
            http_response_code(404);
            exit();
        }

        // Validate if answers array matches the number of questions
        if (count($answers) !== count($gameQuestions)) {
            echo json_encode(['response_code' => 1, 'error' => 'Number of answers does not match the number of questions']);
            http_response_code(400);
            exit();
        }

        // Prepare to insert all user answers into game_answers table
        $stmt = $conn->prepare("
            INSERT INTO game_answers (game_id, user_id, question_id, user_answer, is_correct, time_taken)
            VALUES (:game_id, :user_id, :question_id, :user_answer, :is_correct, :time_taken)
        ");

        $conn->beginTransaction();

        $totalScore = 0;

        foreach ($answers as $answer) {
            $questionId = $answer['question_id'];
            $userAnswer = $answer['selected_answer'];

            // Find the correct answer for the current question
            $correctAnswer = null;
            foreach ($gameQuestions as $question) {
                if ($question['question_id'] == $questionId) {
                    $correctAnswer = $question['correct_answer'];
                    break;
                }
            }

            if ($correctAnswer === null) {
                throw new Exception("Invalid question_id: $questionId");
            }

            // Convert both userAnswer and correctAnswer to lowercase for comparison
            $isCorrect = (strtolower($userAnswer) == strtolower($correctAnswer));

            // Calculate points for each answer
            if ($isCorrect) {
                $points = 10;
            } else {
                $points = -5;
            }


            // Insert each answer into game_answers table
            $stmt->bindValue(':game_id', $gameId, PDO::PARAM_STR);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_STR); // Treat user_id as string
            $stmt->bindValue(':question_id', $questionId, PDO::PARAM_STR); // Treat question_id as string
            $stmt->bindValue(':user_answer', $userAnswer, PDO::PARAM_STR); // Store the actual answer
            $stmt->bindValue(':is_correct', $isCorrect ? '1' : '0', PDO::PARAM_STR); // Treat is_correct as string
            // $currentTime = new DateTime(null, new DateTimeZone('UTC')); // Current time in UTC
            $timeTaken = $currentTime - $startTime;
            $stmt->bindValue(':time_taken', $timeTaken, PDO::PARAM_INT);
            if (!$stmt->execute()) {
                throw new Exception("Error executing statement: " . implode(", ", $stmt->errorInfo()));
            }

            // Update total score
            $totalScore += $points;
        }

        // Insert total score into points table
        $stmt = $conn->prepare("
            INSERT INTO points (user_id, points)
            VALUES (:user_id, :points)
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
        $stmt->bindValue(':points', $totalScore, PDO::PARAM_INT);
        $stmt->execute();
        
          // Check if the user exists in the user_level table and update or insert the level
        $checkUserLevelQuery = "SELECT * FROM level_tbl WHERE user_id = :user_id";
        $checkUserLevelStmt = $conn->prepare($checkUserLevelQuery);
        $checkUserLevelStmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
        $checkUserLevelStmt->execute();
        $userLevelData = $checkUserLevelStmt->fetch(PDO::FETCH_ASSOC);

        // Calculate total questions attempted by the user
        $totalAttemptedQuery = " SELECT SUM(total_attempted) AS total_attempted FROM (
        SELECT COUNT(correct_answers) AS total_attempted FROM user_scores WHERE user_id = :user_id
        UNION ALL
        SELECT COUNT(is_correct) AS total_attempted FROM game_answers WHERE user_id = :user_id AND is_correct = 1
    ) AS combined_attempts
";
        $totalAttemptedStmt = $conn->prepare($totalAttemptedQuery);
        $totalAttemptedStmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
        $totalAttemptedStmt->execute();
        $totalAttemptedQuestions = $totalAttemptedStmt->fetchColumn();

        $newLevel = floor($totalAttemptedQuestions / 1000) + 1;

        if ($userLevelData) {
            // Update existing user level
            $updateUserLevelQuery = "
                UPDATE level_tbl 
                SET label_name = :label_name, total_questions = :total_questions, timestamp = NOW()
                WHERE user_id = :user_id
            ";
            $updateUserLevelStmt = $conn->prepare($updateUserLevelQuery);
            $updateUserLevelStmt->bindValue(':label_name', $newLevel, PDO::PARAM_STR);
            $updateUserLevelStmt->bindValue(':total_questions', $totalAttemptedQuestions, PDO::PARAM_INT);
            $updateUserLevelStmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
            $updateUserLevelStmt->execute();
        } else {
            // Insert new user level
            $insertUserLevelQuery = "
                INSERT INTO level_tbl (user_id, label_name, total_questions, timestamp) 
                VALUES (:user_id, :label_name, :total_questions, NOW())
            ";
            $insertUserLevelStmt = $conn->prepare($insertUserLevelQuery);
            $insertUserLevelStmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
            $insertUserLevelStmt->bindValue(':label_name',  $newLevel, PDO::PARAM_STR);
            $insertUserLevelStmt->bindValue(':total_questions', $totalAttemptedQuestions, PDO::PARAM_INT);
            $insertUserLevelStmt->execute();
        }
        // Check if both users have submitted their answers
        $stmt = $conn->prepare("
            SELECT COUNT(DISTINCT user_id) AS num_users
            FROM game_answers
            WHERE game_id = :game_id
        ");
        $stmt->bindValue(':game_id', $gameId, PDO::PARAM_STR);
        $stmt->execute();
        $numUsersSubmitted = $stmt->fetchColumn();

        if ($numUsersSubmitted < 2) {
            // Only one user has submitted, wait for the other
            $conn->commit();
            echo json_encode(['response_code' => 0, 'message' => 'Waiting for the other user to submit answers']);
            http_response_code(200);
            exit();
        }

        // Both users have submitted, calculate scores and determine winner
        $stmt = $conn->prepare("
            SELECT user_id, SUM(is_correct) AS num_correct_answers, SUM(CASE WHEN is_correct = 1 THEN 10 ELSE -5 END) AS score, MIN(time_taken) AS min_time
            FROM game_answers
            WHERE game_id = :game_id
            GROUP BY user_id
            ORDER BY score DESC, min_time ASC
        ");
        $stmt->bindValue(':game_id', $gameId, PDO::PARAM_STR);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Determine the winner based on scores and submission times
        $winnerId = null;
        $winnerName = null;
        if (count($results) > 1) {
            $topScore = $results[0]['score'];
            $secondScore = $results[1]['score'];

            if ($topScore > $secondScore || ($topScore == $secondScore && $results[0]['min_time'] < $results[1]['min_time'])) {
                $winnerId = $results[0]['user_id'];
            } else {
                $winnerId = $results[1]['user_id'];
            }

            // Fetch winner's name
            $stmt = $conn->prepare("SELECT exmne_fullname FROM examinee_tbl WHERE exmne_id = :winner_id");
            $stmt->bindValue(':winner_id', $winnerId, PDO::PARAM_STR);
            $stmt->execute();
            $winnerName = $stmt->fetchColumn();
        }

        // Update game session with end time and status closed
        $stmt = $conn->prepare("
            UPDATE game_sessions 
            SET end_time = NOW(), status = 'completed', winner_id = :winner_id
            WHERE game_id = :game_id
        ");
        $stmt->bindValue(':game_id', $gameId, PDO::PARAM_STR);
        $stmt->bindValue(':winner_id', $winnerId, PDO::PARAM_STR);
        $stmt->execute();

        // Commit transaction
        $conn->commit();

        // Respond with game result and winner details
        echo json_encode([
            'response_code' => 0,
            'message' => 'Waiting for the other user to submit answers'
        ]);
        http_response_code(200);

    } catch (PDOException $pdoException) {
        // Rollback transaction on PDO exception
        $conn->rollBack();
        
        // Log the detailed error message
        error_log('PDO Error: ' . $pdoException->getMessage());
        
        // Send summarized error message for production
        echo json_encode(['response_code' => 1, 'error' => 'Database error occurred']);
        http_response_code(500);
    } catch (Exception $e) {
        // Rollback transaction on general exception
        $conn->rollBack();
        
        // Log the detailed error message
        error_log('Error: ' . $e->getMessage());
        
        // Send summarized error message for production
        echo json_encode(['response_code' => 1, 'error' => 'Internal server error occurred']);
        http_response_code(500);
    }
} else {
    echo json_encode(['response_code' => 1, 'error' => 'Invalid request method']);
    http_response_code(405);
}
?>
