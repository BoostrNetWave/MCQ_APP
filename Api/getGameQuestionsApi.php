<?php
header("Content-Type: application/json");
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

include('../conn.php');
session_start();

$requestMethod = $_SERVER['REQUEST_METHOD'];

if ($requestMethod == "GET") {
    // Validate input
    if (!isset($_GET['game_id'])) {
        echo json_encode(['response_code' => 1, 'error' => 'game_id is required']);
        http_response_code(400);
        exit();
    }

    $gameId = $_GET['game_id'];

    // Check if the game session exists and is active
    $checkGameQuery = $conn->prepare("SELECT * FROM game_sessions WHERE game_id = :game_id AND status = 'active'");
    $checkGameQuery->bindValue(':game_id', $gameId, PDO::PARAM_STR);
    $checkGameQuery->execute();
    $gameExists = $checkGameQuery->fetch(PDO::FETCH_ASSOC);

    if (!$gameExists) {
        echo json_encode(['response_code' => 1, 'error' => 'Game not found or not active']);
        http_response_code(404);
        exit();
    }

    // Fetch questions for the game session with options from exam_question_tbl
    $stmt = $conn->prepare("
        SELECT 
            gq.question_id, 
            eq.exam_question AS question, 
            eq.exam_answer AS correct_answer, 
            eq.exam_ch1 AS option1, 
            eq.exam_ch2 AS option2, 
            eq.exam_ch3 AS option3, 
            eq.exam_ch4 AS option4 
        FROM 
            game_question gq 
        JOIN 
            exam_question_tbl eq ON gq.question_id = eq.eqt_id 
        WHERE 
            gq.game_id = :game_id
    ");
    $stmt->bindValue(':game_id', $gameId, PDO::PARAM_STR);
    $stmt->execute();
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$questions) {
        echo json_encode(['response_code' => 1, 'error' => 'No questions found for this game']);
        http_response_code(404);
        exit();
    }

    // Format questions in the desired JSON response format
    $formattedQuestions = [];
    foreach ($questions as $question) {
        // Shuffle options
        $options = [
            $question['correct_answer'],
            $question['option1'],
            $question['option2'],
            $question['option3'],
            $question['option4']
        ];
        shuffle($options);

        // Prepare the question in the required JSON format
        $formattedQuestion = [
            'question_id' => $question['question_id'],
            'question' => $question['question'],
            'correct_answer' => $question['correct_answer'],
            'incorrect_answers' => array_values(array_diff($options, [$question['correct_answer']]))
        ];

        $formattedQuestions[] = $formattedQuestion;
    }

    echo json_encode([
        'response_code' => 0,
        'game_id' => $gameId,
        'questions' => $formattedQuestions
    ]);
    http_response_code(200);
} else {
    echo json_encode(['response_code' => 1, 'error' => 'Invalid request method']);
    http_response_code(405);
}
?>
