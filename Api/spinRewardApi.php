<?php
require_once('../conn.php');
session_start();
date_default_timezone_set('Asia/Kolkata');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['user_id'], $input['reward_amount'])) {
        $user_id = $input['user_id'];
        $reward = $input['reward_amount'];

        if (!isset($input['answer'])) {
            // Spin the wheel and get a question
            $currentDateTime = date('Y-m-d H:i:s');
            $currentDate = date('Y-m-d');

            // Check the number of spins today
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM points WHERE user_id = ? AND DATE(timestamp) = ? AND description = 'spin reward'");
            $stmt->execute([$user_id, $currentDate]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result['count'] >= 2) {
                header("HTTP/1.1 400 Bad Request");
                echo json_encode(['error' => 'User has already been rewarded twice today. Try again tomorrow']);
            } else {
                // Get a random question from the exam_question_tbl that the user hasn't received before
                $questionStmt = $conn->prepare(
                    "SELECT eqt_id, exam_question, exam_answer, exam_ch1, exam_ch2, exam_ch3, exam_ch4 
                     FROM exam_question_tbl 
                     WHERE eqt_id NOT IN (
                        SELECT spin_question 
                        FROM points 
                        WHERE user_id = ? 
                        AND spin_question IS NOT NULL
                     ) 
                     ORDER BY RAND() LIMIT 1"
                );
                $questionStmt->execute([$user_id]);
                $question = $questionStmt->fetch(PDO::FETCH_ASSOC);

                if ($question) {
                    $question_id = $question['eqt_id'];
                    $correct_answer = $question['exam_answer'];
                    $question_text = $question['exam_question'];
                    $choices = [
                        $question['exam_ch1'],
                        $question['exam_ch2'],
                        $question['exam_ch3'],
                        $question['exam_ch4']
                    ];

                    shuffle($choices); // Shuffle the choices to randomize the order

                    // Insert the spin record with the question ID
                    $stmt = $conn->prepare("INSERT INTO points (user_id, points, timestamp, description, spin_question) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$user_id, "0", $currentDateTime, "spin reward", $question_id]);

                    $response = [
                        "type" => "multiple",
                        "category" => "Spin Question",
                        "question_id" => $question_id,
                        "question" => $question_text,
                        "correct_answer" => $correct_answer,
                        "incorrect_answers" => array_values(array_diff($choices, [$correct_answer]))
                    ];

                    header('Content-Type: application/json');
                    echo json_encode($response);
                } else {
                    header("HTTP/1.1 500 Internal Server Error");
                    echo json_encode(['error' => 'No new questions available']);
                }
            }
        } else {
            // Validate the answer
            $user_answer = $input['answer'];
            $question_id = $input['question_id'];

            // Check if the user has already submitted an answer for this question
            $checkStmt = $conn->prepare("SELECT * FROM points WHERE user_id = ? AND spin_question = ? AND spin_answer IS NOT NULL");
            $checkStmt->execute([$user_id, $question_id]);
            $checkResult = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($checkResult) {
                header("HTTP/1.1 400 Bad Request");
                echo json_encode(['error' => 'You have already submitted an answer for this question.']);
            } else {
                // Fetch the correct answer
                $answerStmt = $conn->prepare("SELECT exam_answer FROM exam_question_tbl WHERE eqt_id = ?");
                $answerStmt->execute([$question_id]);
                $answerResult = $answerStmt->fetch(PDO::FETCH_ASSOC);

                if ($answerResult && $user_answer === $answerResult['exam_answer']) {
                    // Correct answer, update the record with the reward and answer
                    $currentDateTime = date('Y-m-d H:i:s');
                    $stmt = $conn->prepare("UPDATE points SET points = ?, timestamp = ?, spin_answer = ? WHERE user_id = ? AND spin_question = ?");
                    $stmt->execute([$reward, $currentDateTime, $user_answer, $user_id, $question_id]);
                    header('Content-Type: application/json');
                    echo json_encode(['message' => 'Rewarded successfully']);
                } else {
                    // Incorrect answer, update the record with the answer only
                    $stmt = $conn->prepare("UPDATE points SET spin_answer = ? WHERE user_id = ? AND spin_question = ?");
                    $stmt->execute([$user_answer, $user_id, $question_id]);
                    header("HTTP/1.1 400 Bad Request");
                    echo json_encode(['error' => 'Incorrect answer. Try again']);
                }
            }
        }
    } else {
        header("HTTP/1.1 400 Bad Request");
        echo json_encode(['error' => 'Invalid input data']);
    }
} else {
    header("HTTP/1.1 405 Method Not Allowed");
    echo json_encode(['error' => 'Method not allowed']);
}
?>
