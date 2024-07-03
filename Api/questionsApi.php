<?php
session_start();
include('../conn.php');
header("Content-Type: application/json");
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

$requestMethod = $_SERVER['REQUEST_METHOD'];

if ($requestMethod == "GET") {
    if (!isset($_GET['user_id']) || !isset($_GET['exam_id'])) {
        echo json_encode(['response_code' => 1, 'error' => 'user_id and exam_id are required']);
        http_response_code(400);
        exit();
    }

    $userId = $_GET['user_id']; // Assuming user_id is a VARCHAR (string)
    $examId = intval($_GET['exam_id']);
    $limit = 20;

    try {
        // Set the time zone to IST
        $conn->exec("SET time_zone = '+05:30'");

        // Fetch 20 unique questions that haven't been sent to the user previously
        $selQuest = $conn->prepare("
            SELECT eqt.eqt_id, eqt.exam_question, eqt.exam_ch1, eqt.exam_ch2, eqt.exam_ch3, eqt.exam_ch4, eqt.exam_answer
            FROM exam_question_tbl eqt
            LEFT JOIN user_attempts ua ON eqt.eqt_id = ua.question_id AND ua.user_id = :user_id
            WHERE ua.user_id IS NULL AND eqt.exam_id = :exam_id
            ORDER BY RAND() LIMIT :limit
        ");
        $selQuest->bindParam(':user_id', $userId, PDO::PARAM_STR); // Bind as string
        $selQuest->bindParam(':exam_id', $examId, PDO::PARAM_INT);
        $selQuest->bindParam(':limit', $limit, PDO::PARAM_INT);
        $selQuest->execute();
        $questions = $selQuest->fetchAll(PDO::FETCH_ASSOC);

        if (empty($questions)) {
            echo json_encode(['response_code' => 0, 'results' => [], 'message' => 'No more questions available.']);
            exit();
        }

        // Track which questions were sent to the user
        $insertAttempt = $conn->prepare("
            INSERT INTO user_attempts (user_id, exam_id, question_id, timestamp) VALUES (:user_id, :exam_id, :question_id, NOW())
        ");

        foreach ($questions as $question) {
            try {
                $insertAttempt->bindParam(':user_id', $userId, PDO::PARAM_STR); // Bind as string
                $insertAttempt->bindParam(':exam_id', $examId, PDO::PARAM_INT);
                $insertAttempt->bindParam(':question_id', $question['eqt_id'], PDO::PARAM_INT);
                $insertAttempt->execute();
            } catch (PDOException $e) {
                // Log or echo the specific error for debugging
                echo json_encode(['response_code' => 1, 'error' => 'Insertion Error: ' . $e->getMessage()]);
                http_response_code(500);
                exit();
            }
        }

        // Prepare the response
        $results = [];
        foreach ($questions as $question) {
            $options = [
                $question['exam_ch1'],
                $question['exam_ch2'],
                $question['exam_ch3'],
                $question['exam_ch4'],
                $question['exam_answer']
            ];

            // Shuffle the options
            shuffle($options);

            // Find the correct answer position
            $correctAnswer = $question['exam_answer'];
            $correctAnswerKey = array_search($correctAnswer, $options);

            // Prepare incorrect answers by removing the correct answer
            $incorrectAnswers = array_values(array_diff($options, [$correctAnswer]));

            $results[] = [
                'type' => 'multiple',
                'category' => 'All In One',
                'question_id' => $question['eqt_id'],
                'question' => $question['exam_question'],
                'correct_answer' => $correctAnswer,
                'incorrect_answers' => $incorrectAnswers
            ];
        }

        echo json_encode(['response_code' => 0, 'results' => $results]);
    } catch (Exception $e) {
        echo json_encode(['response_code' => 1, 'error' => 'General Error: ' . $e->getMessage()]);
        http_response_code(500);
    }
} else {
    echo json_encode(['response_code' => 1, 'error' => 'Invalid request method']);
    http_response_code(405); // Method Not Allowed
}
?>
