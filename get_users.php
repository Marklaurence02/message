<?php
session_name("user_session");
session_start();
require_once '../assets/config.php'; // Include your database configuration file

// Check if user ID is set in the session
if (!isset($_SESSION['user_id'])) {
    // Handle the case where the user is not logged in
    die(json_encode(['error' => 'User  not logged in.']));
}

// Get the current user ID from the session
$currentUserId = $_SESSION['user_id'];

// Function to get assigned staff based on user ID
function getAssignedStaff($userId) {
    global $conn; // Use the global connection variable

    $stmt = $conn->prepare("SELECT assigned_staff_id FROM user_staff_assignments WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return $result->fetch_assoc()['assigned_staff_id'];
    }
    return null;
}

// Function to check if the staff is online
function isStaffOnline($staffId) {
    global $conn;

    $stmt = $conn->prepare("SELECT status FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $staffId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return $result->fetch_assoc()['status'] === 'online';
    }
    return false;
}

// Function to save chat messages
function saveChatMessage($senderId, $receiverId, $message) {
    global $conn;

    $stmt = $conn->prepare("INSERT INTO chat_messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $senderId, $receiverId, $message);
    $stmt->execute();
}

// Function to generate a bot response based on user input
function getBotResponse($userMessage) {
    // Here you can implement logic to generate responses based on user input
    // For simplicity, let's return a generic response
    return "Thank you for your question! Unfortunately, I can't provide a specific answer right now. Please check our FAQ or leave a message for our staff.";
}

// Function to handle user requests
function handleUserRequest($userId, $request) {
    $staffId = getAssignedStaff($userId);

    if ($staffId) {
        if (isStaffOnline($staffId)) {
            // Save the message to the chat using the assigned staff ID directly
            saveChatMessage($userId, $staffId, $request);
            return "Your message has been sent to our staff.";
        } else {
            // If the staff is offline, respond with a bot message
            return "The staff member is currently offline. Here's a response from our virtual assistant: " . getBotResponse($request);
        }
    } else {
        return "No staff member is assigned to assist you at the moment.";
    }
}

// Check if the request is coming from a POST method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request = $_POST['message'];

    // Use the current user ID to handle the request
    $response = handleUserRequest($currentUserId, $request);
    echo json_encode(['response' => $response]);
    exit();
}