<?php
header("Content-Type: application/json");
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

include('../conn.php'); 

try {
    // Set error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Query to fetch game session data for a specific user_id
    $stmt = $conn->prepare("
        SELECT 
            gs.game_id,
            gs.user1_id,
            gs.user2_id,
            gs.winner_id,
            exmne1.exmne_fullname AS winner_name,
            (SELECT MAX(time_taken) FROM game_answers WHERE game_id = gs.game_id AND user_id = gs.user1_id) AS user1_total_time_taken,
            (SELECT MAX(time_taken) FROM game_answers WHERE game_id = gs.game_id AND user_id = gs.user2_id) AS user2_total_time_taken,
            exmne1.profile_image AS winner_profile_image,
            exmne2.exmne_fullname AS user1_name,
            exmne2.profile_image AS user1_profile_image,
            exmne3.exmne_fullname AS user2_name,
            exmne3.profile_image AS user2_profile_image,
            (SELECT COUNT(*) FROM game_answers WHERE game_id = gs.game_id AND user_id = gs.user1_id AND is_correct IN (1, 2)) AS user1_correct_answers,
            (SELECT COUNT(*) FROM game_answers WHERE game_id = gs.game_id AND user_id = gs.user2_id AND is_correct IN (1, 2)) AS user2_correct_answers,
            COALESCE((SELECT SUM(points) FROM points WHERE user_id = gs.user1_id AND timestamp = ga1.answer_timestamp), 0) AS user1_points,
            COALESCE((SELECT SUM(points) FROM points WHERE user_id = gs.user2_id AND timestamp = ga2.answer_timestamp), 0) AS user2_points
        FROM 
            game_sessions gs
        LEFT JOIN 
            examinee_tbl exmne1 ON gs.winner_id = exmne1.exmne_id
        LEFT JOIN 
            examinee_tbl exmne2 ON gs.user1_id = exmne2.exmne_id
        LEFT JOIN 
            examinee_tbl exmne3 ON gs.user2_id = exmne3.exmne_id
        LEFT JOIN 
            game_answers ga1 ON gs.game_id = ga1.game_id AND gs.user1_id = ga1.user_id
        LEFT JOIN 
            game_answers ga2 ON gs.game_id = ga2.game_id AND gs.user2_id = ga2.user_id
        WHERE 
            gs.status = 'completed' AND
            (gs.user1_id = :user_id OR gs.user2_id = :user_id)
        GROUP BY 
            gs.game_id
        ORDER BY 
            gs.end_time DESC
    ");
    $stmt->bindValue(':user_id', $_GET['user_id'], PDO::PARAM_STR);
    $stmt->execute();
    $userGameData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($userGameData as &$gameData) {
        $gameData['winner_profile_image'] = $gameData['winner_profile_image'] ? 'https://getlearner.com/' . str_replace('../', '', $gameData['winner_profile_image']) : null;
        $gameData['user1_profile_image'] = $gameData['user1_profile_image'] ? 'https://getlearner.com/' . str_replace('../', '', $gameData['user1_profile_image']) : null;
        $gameData['user2_profile_image'] = $gameData['user2_profile_image'] ? 'https://getlearner.com/' . str_replace('../', '', $gameData['user2_profile_image']) : null;
    }

    // Respond with user game data
    echo json_encode(['response_code' => 0, 'user_game_data' => $userGameData]);
    http_response_code(200);

} catch (PDOException $pdoException) {
    // Log the detailed error message
    error_log('PDO Error: ' . $pdoException->getMessage());

    // Send detailed error message for debugging (remove in production)
    echo json_encode(['response_code' => 1, 'error' => 'Database error occurred', 'details' => $pdoException->getMessage()]);
    http_response_code(500);
} catch (Exception $e) {
    // Log the detailed error message
    error_log('Error: ' . $e->getMessage());

    // Send detailed error message for debugging (remove in production)
    echo json_encode(['response_code' => 1, 'error' => 'Internal server error occurred', 'details' => $e->getMessage()]);
    http_response_code(500);
}
?>
