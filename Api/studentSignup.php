<?php 
header("Content-Type: application/json");
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Method: POST');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Request-with');

require_once '../conn.php';

$requestmethod = $_SERVER['REQUEST_METHOD'];

if ($requestmethod == "POST") {
    // Assuming you're receiving user details in the request body
    $data = json_decode(file_get_contents("php://input"), true);
    
    if(isset($data['fullname']) && isset($data['email']) && isset($data['password'])) {
        $fullname = $data['fullname'];
        $email = $data['email'];
        $password = password_hash($data['password'], PASSWORD_DEFAULT); // Hash the password
        $uuid = bin2hex(random_bytes(16)); // Generate a pseudo-UUID

        try {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT * FROM examinee_tbl WHERE exmne_email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Email already exists
                $response = array(
                    'status' => 409,
                    'message' => 'Email already exists'
                );
                header("HTTP/1.0 409 Conflict");
                echo json_encode($response);
                exit;
            } else {
                // Insert new user
                $stmt = $conn->prepare("INSERT INTO examinee_tbl (exmne_id, exmne_fullname, exmne_email, exmne_password) VALUES (?, ?, ?, ?)");
                $stmt->execute([$uuid, $fullname, $email, $password]);
                
                // Check if user was successfully inserted
                if ($stmt->rowCount() > 0) {
                    $response = array(
                        'status' => 201,
                        'message' => 'User created successfully',
                        'user_id' => $uuid // Return the generated UUID in the response
                    );
                    header("HTTP/1.0 201 Created");
                    echo json_encode($response);
                    exit;
                } else {
                    // Log insertion failure
                    error_log("Failed to insert user into database", 0);
                    
                    $response = array(
                        'status' => 500,
                        'message' => 'Failed to create user'
                    );
                    header("HTTP/1.0 500 Internal Server Error");
                    echo json_encode($response);
                    exit;
                }
            }
        } catch (PDOException $e) {
            // Log database errors
            error_log("Database Error: " . $e->getMessage(), 0);
            $response = array(
                'status' => 500,
                'message' => 'Internal Server Error'
            );
            header("HTTP/1.0 500 Internal Server Error");
            echo json_encode($response);
            exit;
        }
    } else {
        // Invalid request
        $response = array(
            'status' => 400,
            'message' => 'Bad request. Full name, email, and password are required.'
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
