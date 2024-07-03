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
            SELECT b.question_id, q.exam_question AS question, q.exam_ch1, q.exam_ch2, q.exam_ch3, q.exam_ch4, q.exam_answer AS correct_answer
            FROM bookmarks b
            JOIN exam_question_tbl q ON b.question_id = q.eqt_id
            WHERE b.user_id = :user_id
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
        $stmt->execute();
        $bookmarkedQuestions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $formattedQuestions = [];

        foreach ($bookmarkedQuestions as $question) {
            $incorrect_answers = [
                $question['exam_ch1'],
                $question['exam_ch2'],
                $question['exam_ch3'],
                $question['exam_ch4']
            ];

            // Remove correct answer from incorrect answers
            $correct_answer_index = array_search($question['correct_answer'], $incorrect_answers);
            if ($correct_answer_index !== false) {
                unset($incorrect_answers[$correct_answer_index]);
            }

            $formattedQuestion = [
                'question_id' => $question['question_id'],
                'question' => $question['question'],
                'correct_answer' => $question['correct_answer'],
                'incorrect_answers' => array_values($incorrect_answers) // Re-index array after unset
            ];
            $formattedQuestions[] = $formattedQuestion;
        }

        if (empty($formattedQuestions)) {
            echo json_encode(['response_code' => 0, 'message' => 'No questions bookmarked']);
        } else {
            echo json_encode(['response_code' => 0, 'bookmarked_questions' => $formattedQuestions]);
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
