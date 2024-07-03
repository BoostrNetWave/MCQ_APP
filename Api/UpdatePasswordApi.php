<?php
// Include database connection
include("../conn.php");

// Set headers for JSON response and CORS
header("Content-Type: application/json");
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

// Check if the request method is POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve data from JSON payload
    $data = json_decode(file_get_contents("php://input"), true);

    // Retrieve examinee ID, password, and confirmed password from JSON data
    $user_id = isset($data['user_id']) ? $data['user_id'] : '';
    $password = isset($data['password']) ? $data['password'] : '';
    $cpassword = isset($data['cpassword']) ? $data['cpassword'] : '';

    // Input validation
    if (!empty($user_id) && !empty($password) && !empty($cpassword) && $password === $cpassword) {
        // Hash the password
        $hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            // Prepare SQL statement using PDO
            $stmt = $conn->prepare("UPDATE examinee_tbl SET exmne_password=:password WHERE exmne_id=:exmne_id");
            $stmt->bindParam(':password', $hash);
            $stmt->bindParam(':exmne_id', $user_id);

            // Execute the statement
            if ($stmt->execute()) {
                // Password updated successfully
                http_response_code(200); // HTTP status code 200 - OK
                $res = array("status" => "success", "message" => "Password updated successfully");
            } else {
                // Failed to update password
                http_response_code(500); // HTTP status code 500 - Internal Server Error
                $res = array("status" => "error", "message" => "Failed to update password");
            }
        } catch (PDOException $e) {
            // PDO Exception handling
            http_response_code(500); // HTTP status code 500 - Internal Server Error
            $res = array("status" => "error", "message" => "PDO Exception: " . $e->getMessage());
        }
    } else {
        // Passwords don't match or empty parameters
        http_response_code(400); // HTTP status code 400 - Bad Request
        $res = array("status" => "error", "message" => "Invalid input data");
    }
} else {
    // Invalid request method
    http_response_code(405); // HTTP status code 405 - Method Not Allowed
    $res = array("status" => "error", "message" => "Method Not Allowed");
}

// Return JSON response
echo json_encode($res);
exit;
?>
