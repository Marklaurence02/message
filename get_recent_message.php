<?php
include '../assets/config.php';
session_name("owner_session");
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Start output buffering to handle any unexpected output
ob_start();

header('Content-Type: application/json');

// Check if user_id is provided in the query and if session has current user ID
if (!isset($_SESSION['user_id'], $_GET['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'User not authenticated or user ID not specified']);
    exit();
}

$currentUserId = $_SESSION['user_id'];
$userId = filter_var($_GET['user_id'], FILTER_VALIDATE_INT);

// Validate the provided user_id
if (!$userId) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid user ID']);
    exit();
}

try {
    // Query to get the most recent message between the current user and the specified user,
    // or between the current user and any assigned general users if the current user is staff.
    $sql = "SELECT cm.message, cm.timestamp, u.first_name
            FROM chat_messages cm
            JOIN users u ON cm.sender_id = u.user_id
            WHERE (cm.sender_id = ? AND cm.receiver_id = ?)
               OR (cm.sender_id = ? AND cm.receiver_id = ?)
               OR (cm.receiver_id IN (SELECT user_id FROM user_staff_assignments WHERE assigned_staff_id = ?))
            ORDER BY cm.timestamp DESC
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new Exception($conn->error);

    // Bind parameters for both sender-receiver directions and the current user
    $stmt->bind_param('iiiii', $currentUserId, $userId, $userId, $currentUserId, $currentUserId);
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch the most recent message or return null if no messages exist
    $message = $result->fetch_assoc();
    $stmt->close();

    // Clear output buffer and output JSON response
    ob_clean();
    echo json_encode([
        'recent_message' => $message ? htmlspecialchars($message['message'], ENT_QUOTES) : null,
        'timestamp' => $message ? $message['timestamp'] : null,
        'name' => $message ? $message['first_name'] : null
    ]);
} catch (Exception $e) {
    error_log("Exception: " . $e->getMessage());
    http_response_code(500);
    ob_clean();
    echo json_encode(['error' => 'Error fetching recent message']);
} finally {
    $conn->close();
    ob_end_flush();  // End output buffering
}
