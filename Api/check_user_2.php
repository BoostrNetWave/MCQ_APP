<?php

include('../conn.php'); // Adjust the path as per your file structure

if ($argc < 2) {
    die("Usage: php check_user_2.php <game_id>\n");
}

$gameId = $argv[1];

// Polling mechanism to check if user2 joins within the time frame
$startTime = time();
$timeout = 180; // 180 seconds timeout

while ((time() - $startTime) < $timeout) {
    $checkUser2Query = $conn->prepare("SELECT user2_id FROM game_sessions WHERE game_id = :game_id");
    $checkUser2Query->bindValue(':game_id', $gameId, PDO::PARAM_STR);
    $checkUser2Query->execute();
    $gameSession = $checkUser2Query->fetch(PDO::FETCH_ASSOC);

    if (!empty($gameSession['user2_id'])) {
        // User2 joined, exit the loop
        exit();
    }

    usleep(500000); // Sleep for 0.5 seconds
}

// If no user2 joined within the timeout period, update the game session to completed
$updateSql = "DELETE FROM game_sessions WHERE game_id = :game_id";
$updateStmt = $conn->prepare($updateSql);
$updateStmt->bindValue(':game_id', $gameId, PDO::PARAM_STR);
$updateStmt->execute();

?>
