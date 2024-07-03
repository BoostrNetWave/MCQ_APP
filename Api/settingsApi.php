<?php
header("Content-Type: application/json");
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

include("../conn.php");

$requestMethod = $_SERVER['REQUEST_METHOD'];

if ($requestMethod === "GET") {
    // Assuming you receive user_id as a query parameter
    $loggedInUserId = isset($_GET['user_id']) ? $_GET['user_id'] : null;

    if (!$loggedInUserId) {
        http_response_code(400);
        echo json_encode(['response_code' => 1, 'error' => 'user_id parameter is required']);
        exit();
    }

    try {
        // Fetch user details (full name, class, school, state, city)
        $userDetailsQuery = "
            SELECT exmne_fullname as fullname, class, school, state, city
            FROM examinee_tbl
            WHERE exmne_id = :user_id
        ";
        $userDetailsStmt = $conn->prepare($userDetailsQuery);
        $userDetailsStmt->bindValue(':user_id', $loggedInUserId, PDO::PARAM_STR);
        $userDetailsStmt->execute();
        $userDetails = $userDetailsStmt->fetch(PDO::FETCH_ASSOC);

        if (!$userDetails) {
            http_response_code(404);
            echo json_encode(['response_code' => 1, 'error' => 'User not found']);
            exit();
        }

        // Fetch total points (coins)
        $totalPointsQuery = "
            SELECT SUM(points) AS Coins
            FROM points
            WHERE user_id = :user_id
        ";
        $totalPointsStmt = $conn->prepare($totalPointsQuery);
        $totalPointsStmt->bindValue(':user_id', $loggedInUserId, PDO::PARAM_STR);
        $totalPointsStmt->execute();
        $totalPoints = $totalPointsStmt->fetchColumn();

        // Fetch total rewards
        $totalRewardsQuery = "
            SELECT SUM(rewards) AS Rewards
            FROM rewards
            WHERE user_id = :user_id
        ";
        $totalRewardsStmt = $conn->prepare($totalRewardsQuery);
        $totalRewardsStmt->bindValue(':user_id', $loggedInUserId, PDO::PARAM_STR);
        $totalRewardsStmt->execute();
        $totalRewards = $totalRewardsStmt->fetchColumn();

        $levelQuery = "
            SELECT label_name AS Level
            FROM level_tbl
            WHERE user_id = :user_id
        ";
        $levelQueryStmt = $conn->prepare($levelQuery);
        $levelQueryStmt->bindValue(':user_id', $loggedInUserId, PDO::PARAM_STR);
        $levelQueryStmt->execute();
        $rewardLevel = $levelQueryStmt->fetchColumn();
        
        
        // Prepare achievement data
        $achievementData = [
            'fullname' => $userDetails['fullname'],
            'class' => $userDetails['class'],
            'school' => $userDetails['school'],
            'state' => $userDetails['state'],
            'city' => $userDetails['city'],
            'total_points' => intval($totalPoints),
            'total_rewards' => intval($totalRewards),
            'user_level' => $rewardLevel,
        ];

        http_response_code(200);
        echo json_encode([
            'response_code' => 0,
            'achievement_data' => $achievementData,
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
