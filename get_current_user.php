<?php
session_name("owner_session");
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}header('Content-Type: application/json');

// Turn on error logging only if in development (remove or change to '0' in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once '../assets/config.php';

try {
    // Check if the session has the necessary data
    if (isset($_SESSION['username'], $_SESSION['role'], $_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];

        // Prepare and execute the query to retrieve user status
        $status_sql = "SELECT status FROM users WHERE user_id = ?";
        $status_stmt = $conn->prepare($status_sql);

        if ($status_stmt) {
            $status_stmt->bind_param('i', $user_id);
            $status_stmt->execute();
            $status_result = $status_stmt->get_result();

            // Check if the status was retrieved successfully
            if ($status_result->num_rows > 0) {
                $row = $status_result->fetch_assoc();
                echo json_encode([
                    'username' => $_SESSION['username'],
                    'role' => $_SESSION['role'],
                    'user_id' => $_SESSION['user_id'],
                    'status' => $row['status']
                ]);
            } else {
                // Log error if user status is not found and respond with 404
                error_log("User status not found for user_id: $user_id");
                http_response_code(404);
                echo json_encode(['error' => 'User status not found']);
            }
            $status_stmt->close();
        } else {
            // Log database preparation error and respond with 500
            error_log("Database query preparation error: " . $conn->error);
            http_response_code(500);
            echo json_encode(['error' => 'Database query error']);
        }
    } else {
        // Respond with 401 if the user is not authenticated
        http_response_code(401);
        echo json_encode(['error' => 'User not authenticated']);
    }
} catch (Exception $e) {
    // Log any unexpected exceptions and respond with 500
    error_log("Exception occurred: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An unexpected error occurred']);
}

// Close the database connection if it exists
if (isset($conn) && $conn) {
    $conn->close();
}
