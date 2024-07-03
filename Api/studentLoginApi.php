<?php 
header("Content-Type: application/json");
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Method: POST');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Request-with');

require_once '../conn.php';

$requestmethod = $_SERVER['REQUEST_METHOD'];

if ($requestmethod == "POST") {
    // Assuming you're receiving email and password in the request body
    $data = json_decode(file_get_contents("php://input"), true);
    
    if(isset($data['email']) && isset($data['password'])) {
        $email = $data['email'];
        $password = $data['password'];
        
        // Query to retrieve user based on email
        $stmt = $conn->prepare("SELECT * FROM examinee_tbl WHERE exmne_email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Check password
            if (password_verify($password, $user['exmne_password'])) {
                // Password correct, return user data
                $response = array(
                    'status' => 200,
                    'message' => 'Login successful',
                    'data' => array(
                        'email' => $user['exmne_email'],
                        'user_id'  =>$user['exmne_id'],
                    )
                );
                header("HTTP/1.0 200 OK");
                echo json_encode($response, JSON_PRETTY_PRINT);
                exit;
            } else {
                // Password incorrect
                $response = array(
                    'status' => 401,
                    'message' => 'Incorrect password'
                );
                header("HTTP/1.0 401 Unauthorized");
                echo json_encode($response);
                exit;
            }
        } else {
            // User not found
            $response = array(
                'status' => 404,
                'message' => 'User not found'
            );
            header("HTTP/1.0 404 Not Found");
            echo json_encode($response);
            exit;
        }
    } else {
        // Invalid request
        $response = array(
            'status' => 400,
            'message' => 'Bad request. Email and password are required.'
        );
        header("HTTP/1.0 400 Bad Request");
        echo json_encode($response);
        exit;
    }
} else {
    // Method not allowed
    $response = array(
        'status' => 405,
        'message' => $requestmethod . " method is not allowed."
    );
    header("HTTP/1.0 405 Method Not Allowed");
    echo json_encode($response);
    exit;
}
?>
