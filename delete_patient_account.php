<?php
session_start();
require_once __DIR__ . '/helpers.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    $token = null;
    $headers = getallheaders();
    
    if (isset($headers['Authorization'])) {
        $token = trim(str_replace('Bearer', '', $headers['Authorization']));
    } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $token = trim(str_replace('Bearer', '', $_SERVER['HTTP_AUTHORIZATION']));
    }
    
    if ($token) {
        $info = validate_token($conn, $token);
        if (!$info || $info['user_type'] !== 'patient') {
            json_response(false, "Unauthorized");
        }
        $patientId = $info['user_id'];
    } else {
        json_response(false, "Unauthorized");
    }
} else {
    $patientId = $_SESSION['user_id'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $conn->prepare("DELETE FROM auth_tokens WHERE user_type = 'patient' AND user_id = ?");
    $stmt->bind_param("i", $patientId);
    $stmt->execute();
    
    $stmt = $conn->prepare("DELETE FROM patients WHERE id = ?");
    $stmt->bind_param("i", $patientId);
    
    if ($stmt->execute()) {
        session_destroy();
        json_response(true, "Account deleted successfully");
    } else {
        json_response(false, "Failed to delete account: " . $stmt->error);
    }
} else {
    json_response(false, "Invalid request method");
}
?>