<?php
session_start();
require_once __DIR__ . '/helpers.php';

// Check if doctor is logged in
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
    $input = json_decode(file_get_contents('php://input'), true);
    $scheduleId = $input['schedule_id'] ?? 0;
    
    if (!$scheduleId) {
        json_response(false, "Schedule ID is required");
    }
    
    // Verify schedule belongs to this doctor
    $stmt = $conn->prepare("SELECT id FROM doctor_schedules WHERE id = ? AND doctor_id = ?");
    $stmt->bind_param("ii", $scheduleId, $doctorId);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows === 0) {
        json_response(false, "Schedule not found");
    }
    
    // Delete schedule
    $stmt = $conn->prepare("DELETE FROM doctor_schedules WHERE id = ?");
    $stmt->bind_param("i", $scheduleId);
    
    if ($stmt->execute()) {
        json_response(true, "Schedule deleted successfully");
    } else {
        json_response(false, "Failed to delete schedule");
    }
} else {
    json_response(false, "Invalid request method");
}
?>