<?php
session_name("owner_session");
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}include '../assets/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $sender = $_SESSION['username'];
    $receiver = htmlspecialchars($_POST['receiver']);
    $message = htmlspecialchars($_POST['message']);

    if (!empty($sender) && !empty($receiver) && !empty($message)) {
        $sql = "INSERT INTO chat_messages (sender, receiver, message) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sss', $sender, $receiver, $message);
        $stmt->execute();
        $stmt->close();

        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
    }
}
$conn->close();

