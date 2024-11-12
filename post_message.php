<?php
include '../assets/config.php';
session_name("owner_session");
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

// Check if the required data is present
if (isset($_SESSION['user_id'], $_POST['receiver'], $_POST['message'])) {
    $senderId = $_SESSION['user_id'];
    $receiverId = intval($_POST['receiver']); // Ensure receiver ID is an integer
    $message = trim($_POST['message']);

    // Check if the message is not empty and not too long
    if (!empty($message)) {
        if (strlen($message) <= 1000) { // Limit message length to prevent abuse
            // Prepare the SQL statement with placeholders to prevent SQL injection
            $sql = "INSERT INTO chat_messages (sender_id, receiver_id, message, timestamp) VALUES (?, ?, ?, NOW())";
            $stmt = $conn->prepare($sql);

            if ($stmt) {
                $stmt->bind_param('iis', $senderId, $receiverId, $message);
                
                if ($stmt->execute()) {
                    // Return a success response
                    echo json_encode(['status' => 'success']);
                } else {
                    // Log detailed error and return a failure response
                    error_log('Database error: ' . $stmt->error);
                    echo json_encode(['status' => 'error', 'message' => 'Error sending message']);
                }

                $stmt->close();
            } else {
                // Log and return an error if the statement preparation fails
                error_log('Statement preparation failed: ' . $conn->error);
                echo json_encode(['status' => 'error', 'message' => 'Failed to prepare statement']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Message is too long']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Message cannot be empty']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'User not authenticated or missing parameters']);
}

$conn->close();

