<?php
// Headers for CORS and content type
header("Content-Type: application/json");
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

// Include database connection
include("../conn.php");
session_start();

// Function to handle file upload from FormData
function handleFormDataUpload() {
    global $conn;

    // Initialize variables to hold data
    $id = $_POST['user_id'];
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $phone_no = $_POST['phone_no'];
    $school = $_POST['school'];
    $class = $_POST['class'];
    $state = $_POST['state'];
    $city = $_POST['city'];
    $image_data = null;
    $image_type = null;
    $image_name = null;

    // Handle profile image upload if present
    if (!empty($_FILES['profile']['tmp_name'])) {
        $image_data = file_get_contents($_FILES['profile']['tmp_name']);
        $image_type = $_FILES['profile']['type'];
        $image_name = $_FILES['profile']['name'];

        // Directory to store uploaded images (ensure it exists and has proper permissions)
        $upload_dir = "../uploads/";

        // Generate a unique filename for the uploaded image
        $image_filename = uniqid() . "_" . basename($image_name);
        $target_path = $upload_dir . $image_filename;

        // Remove old profile image if exists
        $old_profile_path_query = "SELECT profile_image FROM examinee_tbl WHERE exmne_id = :id";
        $stmt_old_path = $conn->prepare($old_profile_path_query);
        $stmt_old_path->bindParam(':id', $id, PDO::PARAM_STR); // Bind as string since exmne_id is VARCHAR
        $stmt_old_path->execute();
        $old_profile_path = $stmt_old_path->fetchColumn();

        if ($old_profile_path && file_exists($old_profile_path)) {
            unlink($old_profile_path); // Remove the old profile image
        }

        // Move uploaded file to the uploads directory
        if (move_uploaded_file($_FILES['profile']['tmp_name'], $target_path)) {
            // Build the update query
            $query = "
                UPDATE examinee_tbl 
                SET 
                    exmne_fullname = :fullname, 
                    exmne_email = :email,
                    phone_no = :phone_no, 
                    class = :class, 
                    school = :school, 
                    state = :state, 
                    city = :city,
                    profile_image = :image_path
                WHERE exmne_id = :id";

            // Prepare the statement
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_STR); // Bind as string since exmne_id is VARCHAR
            $stmt->bindParam(':fullname', $fullname, PDO::PARAM_STR);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->bindParam(':phone_no', $phone_no, PDO::PARAM_STR);
            $stmt->bindParam(':school', $school, PDO::PARAM_STR);
            $stmt->bindParam(':class', $class, PDO::PARAM_STR);
            $stmt->bindParam(':state', $state, PDO::PARAM_STR);
            $stmt->bindParam(':city', $city, PDO::PARAM_STR);
            $stmt->bindParam(':image_path', $target_path, PDO::PARAM_STR);

            // Execute the statement
            if ($stmt->execute()) {
                echo json_encode(["res" => "success"]);
                http_response_code(200); // OK
            } else {
                echo json_encode(["res" => "failed", "error" => $stmt->errorInfo()]);
                http_response_code(500); // Internal Server Error
            }
        } else {
            echo json_encode(["res" => "failed", "message" => "Failed to move uploaded file"]);
            http_response_code(500); // Internal Server Error
        }
    } else {
        echo json_encode(["res" => "failed", "message" => "No profile image uploaded via FormData"]);
        http_response_code(400); // Bad Request
    }
}

// Function to handle file upload from JSON payload
function handleJsonUpload($data) {
    global $conn;

    // Initialize variables to hold data
    $id = $data['user_id'];
    $fullname = $data['fullname'];
    $email = $data['email'];
    $phone_no = $data['phone_no'];
    $school = $data['school'];
    $class = $data['class'];
    $state = $data['state'];
    $city = $data['city'];
    $image_data = null;
    $image_type = null;
    $image_name = null;

    // Handle profile image upload if present
    if (!empty($data['profile']['content'])) {
        // Decode the base64-encoded image data
        $image_data = base64_decode($data['profile']['content']);
        $image_type = $data['profile']['type'];
        $image_name = $data['profile']['name'];

        // Directory to store uploaded images (ensure it exists and has proper permissions)
        $upload_dir = "../uploads/";

        // Generate a unique filename for the uploaded image
        $image_filename = uniqid() . "_" . basename($image_name);
        $target_path = $upload_dir . $image_filename;

        // Remove old profile image if exists
        $old_profile_path_query = "SELECT profile_image FROM examinee_tbl WHERE exmne_id = :id";
        $stmt_old_path = $conn->prepare($old_profile_path_query);
        $stmt_old_path->bindParam(':id', $id, PDO::PARAM_STR); // Bind as string since exmne_id is VARCHAR
        $stmt_old_path->execute();
        $old_profile_path = $stmt_old_path->fetchColumn();

        if ($old_profile_path && file_exists($old_profile_path)) {
            unlink($old_profile_path); // Remove the old profile image
        }

        // Save the decoded image data to the target path
        if (file_put_contents($target_path, $image_data)) {
            // Build the update query
            $query = "
                UPDATE examinee_tbl 
                SET 
                    exmne_fullname = :fullname, 
                    exmne_email = :email,
                    phone_no = :phone_no, 
                    class = :class, 
                    school = :school, 
                    state = :state, 
                    city = :city,
                    profile_image = :image_path
                WHERE exmne_id = :id";

            // Prepare the statement
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_STR); // Bind as string since exmne_id is VARCHAR
            $stmt->bindParam(':fullname', $fullname, PDO::PARAM_STR);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->bindParam(':phone_no', $phone_no, PDO::PARAM_STR);
            $stmt->bindParam(':school', $school, PDO::PARAM_STR);
            $stmt->bindParam(':class', $class, PDO::PARAM_STR);
            $stmt->bindParam(':state', $state, PDO::PARAM_STR);
            $stmt->bindParam(':city', $city, PDO::PARAM_STR);
            $stmt->bindParam(':image_path', $target_path, PDO::PARAM_STR);

            // Execute the statement
            if ($stmt->execute()) {
                echo json_encode(["res" => "success"]);
                http_response_code(200); // OK
            } else {
                echo json_encode(["res" => "failed", "error" => $stmt->errorInfo()]);
                http_response_code(500); // Internal Server Error
            }
        } else {
            echo json_encode(["res" => "failed", "message" => "Failed to save uploaded file"]);
            http_response_code(500); // Internal Server Error
        }
    } else {
        // No profile image provided
        echo json_encode(["res" => "failed", "message" => "No profile image provided"]);
        http_response_code(400); // Bad Request
    }
}

// Check if the request method is POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if content type is multipart form data
    if (isset($_SERVER["CONTENT_TYPE"]) && strpos($_SERVER["CONTENT_TYPE"], "multipart/form-data") !== false) {
        handleFormDataUpload();
    } else {
        // Assume JSON payload
        $data = json_decode(file_get_contents("php://input"), true);
        handleJsonUpload($data);
    }
} else {
    // Invalid request method
    echo json_encode(["res" => "failed", "message" => "Invalid request method"]);
    http_response_code(405); // Method Not Allowed
}
?>
