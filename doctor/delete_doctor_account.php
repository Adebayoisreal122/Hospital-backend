<?php
session_start();
require_once __DIR__ . '/../includes/helpers.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    $token = null;
    $headers = getallheaders();
    
    if (isset($headers['Authorization'])) {
        $token = trim(str_replace('Bearer', '', $headers['Authorization']));
    } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $token = trim(str_replace('Bearer', '', $_SERVER['HTTP_AUTHORIZATION']));
    }
    
    if ($token) {
        $info = validate_token($conn, $token);
        if (!$info || $info['user_type'] !== 'doctor') {
            json_response(false, "Unauthorized");
        }
        $doctorId = $info['user_id'];
    } else {
        json_response(false, "Unauthorized");
    }
} else {
    $doctorId = $_SESSION['user_id'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $conn->prepare("DELETE FROM auth_tokens WHERE user_type = 'doctor' AND user_id = ?");
    $stmt->bind_param("i", $doctorId);
    $stmt->execute();
    
    $stmt = $conn->prepare("DELETE FROM doctor_schedules WHERE doctor_id = ?");
    $stmt->bind_param("i", $doctorId);
    $stmt->execute();
    
    $stmt = $conn->prepare("UPDATE appointments SET status = 'cancelled' WHERE doctor_id = ? AND appointment_date >= CURDATE()");
    $stmt->bind_param("i", $doctorId);
    $stmt->execute();
    
    $stmt = $conn->prepare("DELETE FROM doctors WHERE id = ?");
    $stmt->bind_param("i", $doctorId);
    
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