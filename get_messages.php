<?php
// get_messages
include '../assets/config.php';
session_name("owner_session");
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Ensure output buffering is started to avoid any unexpected output
ob_start();

if (!isset($_SESSION['user_id'], $_GET['receiver'])) {
    http_response_code(401);
    echo json_encode(['error' => 'User not authenticated or receiver not specified']);
    exit();
}

$senderId = $_SESSION['user_id'];
$receiverId = filter_var($_GET['receiver'], FILTER_VALIDATE_INT);

if (!$receiverId) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid receiver ID']);
    exit();
}

try {
    // Fetch messages between sender and receiver, in both directions
    $sql = "SELECT cm.sender_id, cm.receiver_id, cm.message, cm.timestamp, u.first_name 
            FROM chat_messages cm
            JOIN users u ON cm.sender_id = u.user_id
            WHERE (cm.sender_id = ? AND cm.receiver_id = ?)
               OR (cm.sender_id = ? AND cm.receiver_id = ?)
            ORDER BY cm.timestamp ASC";  // Use ASC to display in chronological order
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new Exception($conn->error);

    $stmt->bind_param('iiii', $senderId, $receiverId, $receiverId, $senderId);
    $stmt->execute();
    $result = $stmt->get_result();

    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }

    // Clear output buffer and output JSON
    ob_clean();
    echo json_encode($messages);
    $stmt->close();
} catch (Exception $e) {
    error_log("Exception: " . $e->getMessage());
    http_response_code(500);
    ob_clean();
    echo json_encode(['error' => 'Error fetching messages']);
} finally {
    $conn->close();
    ob_end_flush();  // End output buffering
}
