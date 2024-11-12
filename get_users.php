<?php
session_name("owner_session");
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}header('Content-Type: application/json');
include '../assets/config.php';

try {
    if (!isset($_SESSION['role'], $_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'User not authenticated']);
        exit();
    }

    $rolesToFetch = match ($_SESSION['role']) {
        'Admin' => ['Owner', 'Staff'],
        'Owner' => ['Admin', 'Staff'],
        'Staff' => ['Admin'],
        default => []
    };

    if (empty($rolesToFetch)) {
        http_response_code(404);
        echo json_encode(['error' => 'No users to fetch for your role']);
        exit();
    }

    $rolePlaceholders = implode(',', array_fill(0, count($rolesToFetch), '?'));
    $sql = "SELECT user_id, username, role, status FROM users WHERE role IN ($rolePlaceholders)";
    $stmt = $conn->prepare($sql);

    if (!$stmt) throw new Exception($conn->error);

    $stmt->bind_param(str_repeat('s', count($rolesToFetch)), ...$rolesToFetch);
    $stmt->execute();
    $result = $stmt->get_result();

    echo json_encode($result->fetch_all(MYSQLI_ASSOC));
    $stmt->close();
} catch (Exception $e) {
    error_log("Exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An unexpected error occurred']);
} finally {
    $conn->close();
}
