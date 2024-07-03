<?php
// Headers for CORS and content type
header("Content-Type: application/json");
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

// Include database connection
include("../conn.php");
session_start();

// Check if the request method is GET
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    // Retrieve user ID from query parameters
    $user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;

    if (!$user_id) {
        echo json_encode(["res" => "failed", "message" => "User ID is required"]);
        http_response_code(400); // Bad Request
        exit();
    }

    // Build the select query
    $query = "SELECT exmne_id As user_id, exmne_fullname as fullname, exmne_email as email, phone_no , class, school, state, city, profile_image 
              FROM examinee_tbl 
              WHERE exmne_id = :user_id";

    // Prepare the statement
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

    // Execute the statement
    if ($stmt->execute()) {
        // Fetch user profile data
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($profile) {
            $base_url = 'https://getlearner.com/';
            $profile_image_path_before = $base_url . $profile['profile_image'];
           $profile_image_path= str_replace('../', '', $profile_image_path_before);

            // Remove the profile_image key if not needed in the JSON response
            unset($profile['profile_image']);

            // Add profile image path data to the response
            $response = [
                "res" => "success",
                "profile" => $profile,
                "profile_image_path" => $profile_image_path // Add the path to the profile image
            ];
            echo json_encode($response);
            http_response_code(200); // OK
        } else {
            echo json_encode(["res" => "failed", "message" => "User not found"]);
            http_response_code(404); // Not Found
        }
    } else {
        echo json_encode(["res" => "failed", "error" => $stmt->errorInfo()]);
        http_response_code(500); // Internal Server Error
    }
} else {
    echo json_encode(["res" => "failed", "message" => "Invalid request method"]);
    http_response_code(405); // Method Not Allowed
}
?>
