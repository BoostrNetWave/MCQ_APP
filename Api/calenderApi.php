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
        // Fetch points earned daily including max points in a day
        $pointsQuery = "
            SELECT DATE(timestamp) AS date, SUM(points) AS points
            FROM points
            WHERE user_id = :user_id
            GROUP BY DATE(timestamp)
            ORDER BY DATE(timestamp) DESC
        ";
        $pointsStmt = $conn->prepare($pointsQuery);
        $pointsStmt->bindValue(':user_id', $loggedInUserId, PDO::PARAM_STR);
        $pointsStmt->execute();
        $dailyPoints = $pointsStmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch total earned points
        $totalPointsQuery = "
            SELECT SUM(points) AS total_points_today
            FROM points
            WHERE user_id = :user_id
            AND DATE(timestamp) = CURDATE()
        ";
        $totalPointsStmt = $conn->prepare($totalPointsQuery);
        $totalPointsStmt->bindValue(':user_id', $loggedInUserId, PDO::PARAM_STR);
        $totalPointsStmt->execute();
        $totalPoints = $totalPointsStmt->fetchColumn();

        // Fetch highest points earned in a single day
        $maxPointsQuery = "
            SELECT MAX(points) AS max_points_in_day
            FROM points
            WHERE user_id = :user_id
            GROUP BY DATE(timestamp)
        ";
        $maxPointsStmt = $conn->prepare($maxPointsQuery);
        $maxPointsStmt->bindValue(':user_id', $loggedInUserId, PDO::PARAM_STR);
        $maxPointsStmt->execute();
        $maxPoints = $maxPointsStmt->fetchAll(PDO::FETCH_ASSOC);

        // Extract the highest points earned in a single day from the result
        $highestPointsInDay = 0;
        foreach ($maxPoints as $maxPoint) {
            if ($maxPoint['max_points_in_day'] > $highestPointsInDay) {
                $highestPointsInDay = $maxPoint['max_points_in_day'];
            }
        }

        // Prepare response data
        $response = [
            'response_code' => 0,
            'daily_points' => $dailyPoints,
            'today' => $totalPoints,
            'previous_record' => $highestPointsInDay,
        ];

        http_response_code(200);
        echo json_encode($response);
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
