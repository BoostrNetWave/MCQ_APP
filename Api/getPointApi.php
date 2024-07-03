<?php
header("Content-Type: application/json");
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

include('../conn.php'); // Include your database connection script
session_start();

$requestMethod = $_SERVER['REQUEST_METHOD'];

if ($requestMethod == "GET") {
    // Validate input
    if (!isset($_GET['user_id'])) {
        echo json_encode(['response_code' => 1, 'error' => 'user_id is required']);
        http_response_code(400);
        exit();
    }

    $userId = intval($_GET['user_id']);

    try {
        // Query to fetch total points for the specified user_id
        $stmt = $conn->prepare("
            SELECT SUM(points) AS total_points
            FROM points
            WHERE user_id = :user_id
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $totalPoints = $stmt->fetchColumn();

        if ($totalPoints === false) {
            echo json_encode(['response_code' => 1, 'error' => 'No points found for this user']);
            http_response_code(404);
            exit();
        }

        // Prepare response
        $response = [
            'response_code' => 0,
            'user_id' => $userId,
            'total_points' => intval($totalPoints) // Ensure total_points is an integer
        ];

        echo json_encode($response);
        http_response_code(200);

    } catch (PDOException $pdoException) {
        echo json_encode(['response_code' => 1, 'error' => 'PDO Error: ' . $pdoException->getMessage()]);
        http_response_code(500);
    } catch (Exception $e) {
        echo json_encode(['response_code' => 1, 'error' => 'Error: ' . $e->getMessage()]);
        http_response_code(500);
    }
} else {
    echo json_encode(['response_code' => 1, 'error' => 'Invalid request method']);
    http_response_code(405);
}
?>
