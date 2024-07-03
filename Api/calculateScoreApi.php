<?php
header("Content-Type: application/json");
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

include("../conn.php");

$requestMethod = $_SERVER['REQUEST_METHOD'];

if ($requestMethod === "POST") {
    $input = json_decode(file_get_contents("php://input"), true);

    if (!isset($input['user_id']) || !isset($input['exam_id']) || !isset($input['answers'])) {
        http_response_code(400);
        echo json_encode(['response_code' => 1, 'error' => 'user_id, exam_id, and answers are required']);
        exit();
    }

    $userId = $input['user_id'];
    $examId = $input['exam_id'];
    $answers = $input['answers'];

    $totalQuestions = count($answers);
    $correctAnswers = 0;
    $wrongAnswers = 0;

    try {
        // Calculate correct and wrong answers
        foreach ($answers as $answer) {
            $questionId = intval($answer['question_id']);
            $selectedAnswer = strtolower(trim($answer['selected_answer']));

            $query = "SELECT LOWER(TRIM(exam_answer)) AS exam_answer 
                      FROM exam_question_tbl 
                      WHERE eqt_id = :question_id AND exam_id = :exam_id";

            $stmt = $conn->prepare($query);
            $stmt->bindValue(':question_id', $questionId, PDO::PARAM_INT);
            $stmt->bindValue(':exam_id', $examId, PDO::PARAM_STR);
            $stmt->execute();

            $correctAnswer = $stmt->fetchColumn();

            if ($correctAnswer !== false && $correctAnswer === $selectedAnswer) {
                $correctAnswers++;
            } else {
                $wrongAnswers++;
            }
        }

        // Calculate base score and percentage
        $baseScore = "$correctAnswers/$totalQuestions";
        $baseScorePercentage = round(($correctAnswers / $totalQuestions) * 100, 2);
        $points = ($correctAnswers * 10) - ($wrongAnswers * 2);

        // Insert or update user_scores
        $saveScoreQuery = "
            INSERT INTO user_scores (user_id, exam_id, total_questions, correct_answers, wrong_answers, score, score_percentage, timestamp) 
            VALUES (:user_id, :exam_id, :total_questions, :correct_answers, :wrong_answers, :score, :score_percentage, NOW())
            ON DUPLICATE KEY UPDATE 
                correct_answers = correct_answers + VALUES(correct_answers), 
                wrong_answers = wrong_answers + VALUES(wrong_answers), 
                score = VALUES(score), 
                score_percentage = VALUES(score_percentage), 
                total_questions = total_questions + VALUES(total_questions),
                timestamp = NOW()
        ";

        $saveScoreStmt = $conn->prepare($saveScoreQuery);
        $saveScoreStmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
        $saveScoreStmt->bindValue(':exam_id', $examId, PDO::PARAM_STR);
        $saveScoreStmt->bindValue(':total_questions', $totalQuestions, PDO::PARAM_INT);
        $saveScoreStmt->bindValue(':correct_answers', $correctAnswers, PDO::PARAM_INT);
        $saveScoreStmt->bindValue(':wrong_answers', $wrongAnswers, PDO::PARAM_INT);
        $saveScoreStmt->bindValue(':score', $baseScore, PDO::PARAM_STR);
        $saveScoreStmt->bindValue(':score_percentage', $baseScorePercentage, PDO::PARAM_STR);
        $saveScoreStmt->execute();

        // Insert points into points table
        $savePointsQuery = "
            INSERT INTO points (user_id, exam_id, points, timestamp) 
            VALUES (:user_id, :exam_id, :points, NOW())
        ";

        $savePointsStmt = $conn->prepare($savePointsQuery);
        $savePointsStmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
        $savePointsStmt->bindValue(':exam_id', $examId, PDO::PARAM_STR);
        $savePointsStmt->bindValue(':points', $points, PDO::PARAM_INT);
        $savePointsStmt->execute();

        // Calculate total attempted questions
        $totalQuestionsQuery = "
            SELECT count(question_id) AS total_attempted 
            FROM user_attempts 
            WHERE user_id = :user_id
        ";

        $totalQuestionsStmt = $conn->prepare($totalQuestionsQuery);
        $totalQuestionsStmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
        $totalQuestionsStmt->execute();

        $totalAttempted = $totalQuestionsStmt->fetchColumn();
        $labelThreshold = 1000;
        // Check if label entry exists for the user and exam
        $labelCheckQuery = "
            SELECT label_name 
            FROM label_tbl 
            WHERE user_id = :user_id
        ";

        $labelCheckStmt = $conn->prepare($labelCheckQuery);
        $labelCheckStmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
        $labelCheckStmt->execute();

        $existingLabel = $labelCheckStmt->fetchColumn();

        if ($existingLabel === false) {
            // Insert new label
            $newLabel = 1;
            $insertLabelQuery = "
                INSERT INTO label_tbl (user_id, label_name, total_questions, timestamp) 
                VALUES (:user_id, :label_name, :total_questions, NOW())
            ";

            $insertLabelStmt = $conn->prepare($insertLabelQuery);
            $insertLabelStmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
            $insertLabelStmt->bindValue(':label_name', "Label $newLabel", PDO::PARAM_STR);
            $insertLabelStmt->bindValue(':total_questions', $totalAttempted, PDO::PARAM_INT);
            $insertLabelStmt->execute();
        } else {
            // Update existing label
            $newLabel = floor($totalAttempted / $labelThreshold) + 1;
            $updateLabelQuery = "
                UPDATE label_tbl 
                SET label_name = :label_name, 
                    total_questions = :total_questions, 
                    timestamp = NOW()
                WHERE user_id = :user_id
            ";

            $updateLabelStmt = $conn->prepare($updateLabelQuery);
            $updateLabelStmt->bindValue(':label_name', "Label $newLabel", PDO::PARAM_STR);
            $updateLabelStmt->bindValue(':total_questions', $totalAttempted, PDO::PARAM_INT);
            $updateLabelStmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
            $updateLabelStmt->execute();
        }

        // Respond with success and data
        http_response_code(200);
        echo json_encode([
            'response_code' => 0,
            'score' => $baseScore,
            'percentage' => $baseScorePercentage,
            'total_points' => $points
        ]);
    } catch (PDOException $pdoException) {
        http_response_code(500);
        echo json_encode(['response_code' => 1, 'error' => 'Database error: ' . $pdoException->getMessage()]);
    } catch (Exception $exception) {
        http_response_code(500);
        echo json_encode(['response_code' => 1, 'error' => 'Unexpected error: ' . $exception->getMessage()]);
    }
} else {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['response_code' => 1, 'error' => 'Invalid request method']);
}
?>
