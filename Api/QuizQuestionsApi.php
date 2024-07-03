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
        // Check which questions have already been attempted by the user
        $query = "SELECT question_id FROM user_attempts WHERE user_id = :user_id";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
        $stmt->execute();
        $presentedQuestions = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Fetch 10 random questions that haven't been presented to the user
        $limit = 10; // Number of questions to fetch

        // If there are already attempted questions, prepare the NOT IN clause
        if (!empty($presentedQuestions)) {
            $placeholders = implode(",", array_fill(0, count($presentedQuestions), "?"));
            $whereClause = "AND eqt_id NOT IN ($placeholders)";
        } else {
            $whereClause = ""; // No questions attempted yet
        }

        // Fetch random questions that haven't been attempted
        $query = "SELECT eqt_id AS question_id, exam_question AS question, exam_answer AS correct_answer,
                         exam_ch1, exam_ch2, exam_ch3, exam_ch4
                  FROM exam_question_tbl 
                  WHERE 1 $whereClause
                  ORDER BY RAND() 
                  LIMIT $limit";

        $stmt = $conn->prepare($query);
        $stmt->execute($presentedQuestions);
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($questions)) {
            http_response_code(404);
            echo json_encode(['response_code' => 2, 'error' => 'No questions available']);
            exit();
        }

        // Insert the newly presented questions into user_attempts table
        $insertQuery = "INSERT INTO user_attempts (user_id, question_id, start_time) VALUES ";
        $values = [];
        foreach ($questions as $index => $question) {
            // Ensure each question is inserted only once
            $values[] = "(:user_id$index, :question_id$index, NOW())";
        }
        $insertQuery .= implode(", ", $values);

        $insertStmt = $conn->prepare($insertQuery);
        foreach ($questions as $index => $question) {
            // Bind values with unique parameter names
            $insertStmt->bindValue(":user_id$index", $userId, PDO::PARAM_STR);
            $insertStmt->bindValue(":question_id$index", $question['question_id'], PDO::PARAM_STR);
        }
        $insertStmt->execute();

        // Format response as required
        $formattedQuestions = [];
        foreach ($questions as $question) {
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

        http_response_code(200);
        echo json_encode([
            'response_code' => 0,
            'questions' => $formattedQuestions
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
