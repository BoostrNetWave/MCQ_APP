<?php
header("Content-Type: application/json");
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

include("../conn.php");

try {
    // Fetch the top 100 users ordered by total points
    $query = "
        SELECT u.exmne_id AS user_id, u.exmne_fullname,u.profile_image AS image_url ,SUM(p.points) AS total_points, l.label_name AS level
        FROM examinee_tbl u
        INNER JOIN points p ON u.exmne_id = p.user_id
        INNER JOIN level_tbl l ON u.exmne_id = l.user_id
        GROUP BY u.exmne_id
        ORDER BY total_points DESC
        LIMIT 100
    ";

    $stmt = $conn->query($query);
    $leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Initialize an array to store formatted leaderboard data
    $formattedLeaderboard = [];
    // Iterate through the leaderboard results to format the top 100 users
    foreach ($leaderboard as $key => $user) {
        $rank = $key + 1;
        $formattedUser = [
            'rank' => $rank,
            'user_id' => $user['user_id'],
            'fullname' => $user['exmne_fullname'],
            'points' => intval($user['total_points']),
            'level' => $user['level'],
            'image_url'=>str_replace('../' ,'','https://getlearner.com/'.$user['image_url'])
        ];

        $formattedLeaderboard[] = $formattedUser;
    }

    // Check if user_id is provided in the query parameters
    if (isset($_GET['user_id'])) {
        $requestedUserId = $_GET['user_id'];

        // Initialize user rank info
        $userRankInfo = null;

        // Find the rank of the requested user
        foreach ($formattedLeaderboard as $user) {
            if ($user['user_id'] == $requestedUserId) {
                $rank = $user['rank'];
                $userRankInfo = [
                    'user_id' => $requestedUserId,
                    'all_india_rank' => $rank,
                    'fullname' => $user['fullname'],
                    'points' => $user['points'], 
                    'level' => $user['level'],
                    'image_url' => $user['image_url']
                    
                ];
                break;
            }
        }

        // If user_id is not found in the leaderboard
        if ($userRankInfo === null) {
            http_response_code(404);
            echo json_encode(['response_code' => 1, 'error' => 'User not found in leaderboard']);
            exit;
        }
    }

    // Return the formatted leaderboard JSON response for the top 100 users
    http_response_code(200);
    echo json_encode([
        'response_code' => 0,
        'leaderboard' => $formattedLeaderboard,
        'user_rank' => $userRankInfo
    ]);

} catch (PDOException $pdoException) {
    http_response_code(500);
    echo json_encode(['response_code' => 1, 'error' => 'Database error: ' . $pdoException->getMessage()]);
} catch (Exception $exception) {
    http_response_code(500);
    echo json_encode(['response_code' => 1, 'error' => 'Unexpected error: ' . $exception->getMessage()]);
}
?>
