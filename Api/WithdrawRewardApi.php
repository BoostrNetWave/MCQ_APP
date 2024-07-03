<?php
header("Content-Type: application/json");
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

include("../conn.php");

$requestMethod = $_SERVER['REQUEST_METHOD'];

if ($requestMethod === "POST") {
    $input = json_decode(file_get_contents("php://input"), true);

    if (!isset($input['user_id']) || !isset($input['upi_id']) || !isset($input['amount'])) {
        http_response_code(400);
        echo json_encode(['response_code' => 1, 'error' => 'user_id, upi_id, and amount are required']);
        exit();
    }

    $userId = $input['user_id'];
    $upiId = $input['upi_id'];
    $amount = $input['amount'];

    try {
        // Calculate total points available for conversion
        $pointConvertQuery = "
            SELECT SUM(points) AS total_points 
            FROM points 
            WHERE user_id = :user_id AND is_converted_points = 0
        ";
        $pointConvertStmt = $conn->prepare($pointConvertQuery);
        $pointConvertStmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
        $pointConvertStmt->execute();
        $totalPoints = $pointConvertStmt->fetchColumn();

        // Calculate reward points based on total points (convert to float with precision)
        $rewardPoints = (float) $totalPoints / 2000;

        // Update or insert into reward_tbl based on existing balance
        $checkRewardQuery = "
            SELECT reward_amount 
            FROM reward_tbl 
            WHERE user_id = :user_id
        ";
        $checkRewardStmt = $conn->prepare($checkRewardQuery);
        $checkRewardStmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
        $checkRewardStmt->execute();
        $existingRewardAmount = $checkRewardStmt->fetchColumn();

        if ($existingRewardAmount !== false) {
            // If reward_amount exists, add to the existing amount
            $newRewardAmount = $existingRewardAmount + $rewardPoints;
            $updateRewardQuery = "
                UPDATE reward_tbl 
                SET reward_amount = :reward_amount 
                WHERE user_id = :user_id
            ";
            $updateRewardStmt = $conn->prepare($updateRewardQuery);
            $updateRewardStmt->bindValue(':reward_amount', $newRewardAmount, PDO::PARAM_STR);
            $updateRewardStmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
            $updateRewardStmt->execute();
        } else {
            // If reward_amount doesn't exist, insert new record
            $insertRewardQuery = "
                INSERT INTO reward_tbl (user_id, reward_amount) 
                VALUES (:user_id, :reward_amount)
            ";
            $insertRewardStmt = $conn->prepare($insertRewardQuery);
            $insertRewardStmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
            $insertRewardStmt->bindValue(':reward_amount', $rewardPoints, PDO::PARAM_STR);
            $insertRewardStmt->execute();
        }

         // Update points table to mark points as converted
        $updatePointsQuery = "
            UPDATE points 
            SET is_converted_points = true 
            WHERE user_id = :user_id AND is_converted_points = 0
        ";
        $updatePointsStmt = $conn->prepare($updatePointsQuery);
        $updatePointsStmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
        $updatePointsStmt->execute();
        
        // Check if withdrawal amount exceeds available reward balance
        $checkBalanceQuery = "
            SELECT SUM(reward_amount) AS total_reward 
            FROM reward_tbl 
            WHERE user_id = :user_id
        ";
        $checkBalanceStmt = $conn->prepare($checkBalanceQuery);
        $checkBalanceStmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
        $checkBalanceStmt->execute();
        $currentBalance = $checkBalanceStmt->fetchColumn();

        if ($currentBalance < $amount) {
            http_response_code(400);
            echo json_encode(['response_code' => 1, 'error' => 'Withdrawal amount exceeds available reward balance']);
            exit();
        }

        if ($amount < 50) {
            http_response_code(400);
            echo json_encode(['response_code' => 1, 'error' => 'Withdrawal amount should be greater than 50']);
            exit();
        }
        if ($amount > 2000) {
            http_response_code(400);
            echo json_encode(['response_code' => 1, 'error' => 'Withdrawal amount should not be greater than 2000']);
            exit();
        }

        // Start transaction
        $conn->beginTransaction();

        // Generate unique withdrawal ID
        $withdrawalId = generateWithdrawalId();

        // Insert withdrawal record
        $insertWithdrawalQuery = "
            INSERT INTO withdrawals (withdrawal_id, user_id, upi_id, amount, status, withdrawal_date)
            VALUES (:withdrawal_id, :user_id, :upi_id, :amount, 'pending', NOW())
        ";
        $insertWithdrawalStmt = $conn->prepare($insertWithdrawalQuery);
        $insertWithdrawalStmt->bindValue(':withdrawal_id', $withdrawalId, PDO::PARAM_STR);
        $insertWithdrawalStmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
        $insertWithdrawalStmt->bindValue(':upi_id', $upiId, PDO::PARAM_STR);
        $insertWithdrawalStmt->bindValue(':amount', $amount, PDO::PARAM_INT);
        $insertWithdrawalStmt->execute();

        // Deduct withdrawal amount from reward balance
        $deductRewardQuery = "
            UPDATE reward_tbl 
            SET reward_amount = reward_amount - :amount 
            WHERE user_id = :user_id
        ";
        $deductRewardStmt = $conn->prepare($deductRewardQuery);
        $deductRewardStmt->bindValue(':amount', $amount, PDO::PARAM_INT);
        $deductRewardStmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
        $deductRewardStmt->execute();
        // Calculate remaining balance after withdrawal
        $remainingBalanceQuery = "
            SELECT SUM(reward_amount) AS remaining_balance 
            FROM reward_tbl 
            WHERE user_id = :user_id
        ";
        $remainingBalanceStmt = $conn->prepare($remainingBalanceQuery);
        $remainingBalanceStmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
        $remainingBalanceStmt->execute();
        $remainingBalance = $remainingBalanceStmt->fetchColumn();

        // Commit transaction
        $conn->commit();

        // Format remaining balance to two decimal places
        $remainingBalance = number_format($remainingBalance, 2);

        // Return success response
        $response = [
            'response_code' => 0,
            'message' => 'Withdrawal request submitted successfully. Amount will be credited shortly.',
            'withdrawal_id' => $withdrawalId,
            'remaining_balance' => $remainingBalance,
            'status' => 'pending'
        ];

        http_response_code(200);
        echo json_encode($response);
    } catch (PDOException $pdoException) {
        // Rollback transaction on database error
        $conn->rollBack();
        http_response_code(500);
        echo json_encode(['response_code' => 1, 'error' => 'Database error: ' . $pdoException->getMessage()]);
    } catch (Exception $exception) {
        // Handle unexpected errors
        $conn->rollBack();
        http_response_code(500);
        echo json_encode(['response_code' => 1, 'error' => 'Unexpected error: ' . $exception->getMessage()]);
    }
} else {
    // Handle invalid request method
    http_response_code(405);
    echo json_encode(['response_code' => 1, 'error' => 'Invalid request method']);
}

function generateWithdrawalId() {
    // Generate a unique withdrawal ID
    $prefix = 'WD';
    $timestamp = time();
    $random = mt_rand(1000, 9999);
    $withdrawalId = $prefix . $timestamp . $random;

    return $withdrawalId;
}
?>
