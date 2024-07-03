<?php
header("Content-Type: application/json");
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

include("../conn.php");

$requestMethod = $_SERVER['REQUEST_METHOD'];

if ($requestMethod === "POST") {
    $input = json_decode(file_get_contents("php://input"), true);

    if (!isset($input['user_id']) || !isset($input['answers'])) {
        http_response_code(400);
        echo json_encode(['response_code' => 1, 'error' => 'user_id and answers are required']);
        exit();
    }

    $userId = $input['user_id'];
    $answers = $input['answers'];

    if (!is_array($answers) || count($answers) == 0) {
        http_response_code(400);
        echo json_encode(['response_code' => 1, 'error' => 'answers must be a non-empty array']);
        exit();
    }

    try {
        $correctAnswers = 0;
        $wrongAnswers = 0;
        $totalQuestions = count($answers);

        // Update end time
        $updateEndTimeQuery = "
            UPDATE user_attempts 
            SET end_time = NOW() 
            WHERE user_id = :user_id
        ";
        $updateEndTimeStmt = $conn->prepare($updateEndTimeQuery);
        $updateEndTimeStmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
        $updateEndTimeStmt->execute();

        // Validate if the answers are not already submitted and check if they are in user_attempts table
        $questionIds = array_column($answers, 'question_id');
        $placeholders = str_repeat('?,', count($questionIds) - 1) . '?';

        $checkAttemptsQuery = "
            SELECT question_id, start_time, end_time
            FROM user_attempts
            WHERE user_id = ? AND question_id IN ($placeholders)
        ";
        $checkAttemptsStmt = $conn->prepare($checkAttemptsQuery);
        $checkAttemptsStmt->execute(array_merge([$userId], $questionIds));
        $existingAttempts = $checkAttemptsStmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($existingAttempts) !== count($questionIds)) {
            http_response_code(400);
            echo json_encode(['response_code' => 1, 'error' => 'Some questions have not been attempted by the user']);
            exit();
        }

        $conn->beginTransaction();

        $timeTakenSeconds = null;

        // Process the first answer to calculate time difference
        $firstAnswer = $answers[0];
        $firstQuestionId = intval($firstAnswer['question_id']);
        $firstUserAnswer = strtolower(trim($firstAnswer['selected_answer']));

        $attemptData = array_values(array_filter($existingAttempts, function ($attempt) use ($firstQuestionId) {
            return $attempt['question_id'] == $firstQuestionId;
        }))[0];

        $startTime = new DateTime($attemptData['start_time']);
        $endTime = new DateTime($attemptData['end_time']);
        $timeTakenSeconds = $endTime->getTimestamp() - $startTime->getTimestamp();

        foreach ($answers as $answer) {
            $questionId = intval($answer['question_id']);
            $userAnswer = strtolower(trim($answer['selected_answer']));

            // Check if the score for this question and user already exists
            $checkScoreQuery = "
                SELECT COUNT(*) AS count 
                FROM user_scores 
                WHERE user_id = :user_id 
                AND question_id = :question_id
            ";
            $checkScoreStmt = $conn->prepare($checkScoreQuery);
            $checkScoreStmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
            $checkScoreStmt->bindValue(':question_id', $questionId, PDO::PARAM_INT);
            $checkScoreStmt->execute();
            $scoreExists = $checkScoreStmt->fetchColumn();

            if ($scoreExists > 0) {
                // Score already exists, respond with 'already submitted'
                http_response_code(400);
                echo json_encode(['response_code' => 1, 'error' => 'Answers for questions are already submitted']);
                exit();
            }

            $query = "SELECT exam_answer FROM exam_question_tbl WHERE eqt_id = :question_id";
            $stmt = $conn->prepare($query);
            $stmt->bindValue(':question_id', $questionId, PDO::PARAM_INT);
            $stmt->execute();
            $correctAnswer = strtolower(trim($stmt->fetchColumn()));

            if ($correctAnswer === $userAnswer) {
                $correctAnswers++;
            } else {
                $wrongAnswers++;
            }

            // Insert each answer into user_scores table
            $saveScoreQuery = "
                INSERT INTO user_scores (user_id, question_id, user_answer, correct_answers, wrong_answers, score, score_percentage, total_questions, timestamp)
                VALUES (:user_id, :question_id, :user_answer, :correct_answers, :wrong_answers, :score, :score_percentage, :total_questions, NOW())
            ";
            $saveScoreStmt = $conn->prepare($saveScoreQuery);
            $saveScoreStmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
            $saveScoreStmt->bindValue(':question_id', $questionId, PDO::PARAM_INT);
            $saveScoreStmt->bindValue(':user_answer', $userAnswer, PDO::PARAM_STR);
            $saveScoreStmt->bindValue(':correct_answers', $correctAnswers, PDO::PARAM_INT);
            $saveScoreStmt->bindValue(':wrong_answers', $wrongAnswers, PDO::PARAM_INT);
            $saveScoreStmt->bindValue(':score', "$correctAnswers/$totalQuestions", PDO::PARAM_STR);
            $saveScoreStmt->bindValue(':score_percentage', round(($correctAnswers / $totalQuestions) * 100, 2), PDO::PARAM_STR);
            $saveScoreStmt->bindValue(':total_questions', $totalQuestions, PDO::PARAM_INT);
            $saveScoreStmt->execute();
        }

        $conn->commit();

        $baseScore = "$correctAnswers/$totalQuestions";
        $baseScorePercentage = round(($correctAnswers / $totalQuestions) * 100, 2);
        $points = ($correctAnswers * 10) - ($wrongAnswers * 5);

        // Store the points in the database
        $savePointsQuery = "
            INSERT INTO points (user_id, points, timestamp) 
            VALUES (:user_id, :points, NOW())
        ";
        $savePointsStmt = $conn->prepare($savePointsQuery);
        $savePointsStmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
        $savePointsStmt->bindValue(':points', $points, PDO::PARAM_INT);
        $savePointsStmt->execute();

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
            $updateUserLevelStmt->bindValue(':label_name',$newLevel, PDO::PARAM_STR);
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
            $insertUserLevelStmt->bindValue(':label_name', $newLevel, PDO::PARAM_STR);
            $insertUserLevelStmt->bindValue(':total_questions', $totalAttemptedQuestions, PDO::PARAM_INT);
            $insertUserLevelStmt->execute();
        }

        // Prepare response with time difference for the first question only
        $response = [
            'response_code' => 0,
            'score' => $baseScore,
            'correct_answers' => $correctAnswers,
            'wrong_answers' => $wrongAnswers,
            'points' => $points,
            'time_taken_seconds' => $timeTakenSeconds
        ];

        http_response_code(200);
        echo json_encode($response);
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
