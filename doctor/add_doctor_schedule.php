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
    $input = json_decode(file_get_contents('php://input'), true);
    
    $dayOfWeek = $input['day_of_week'] ?? '';
    $startTime = $input['start_time'] ?? '';
    $endTime = $input['end_time'] ?? '';
    $isAvailable = $input['is_available'] ?? true;
    
    if (empty($dayOfWeek) || empty($startTime) || empty($endTime)) {
        json_response(false, "All fields are required");
    }
    
    $validDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    if (!in_array($dayOfWeek, $validDays)) {
        json_response(false, "Invalid day of week");
    }
    
    $stmt = $conn->prepare("SELECT id FROM doctor_schedules WHERE doctor_id = ? AND day_of_week = ?");
    $stmt->bind_param("is", $doctorId, $dayOfWeek);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        json_response(false, "Schedule for this day already exists");
    }
    
    $stmt = $conn->prepare("
        INSERT INTO doctor_schedules (doctor_id, day_of_week, start_time, end_time, is_available)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->bind_param("isssi", $doctorId, $dayOfWeek, $startTime, $endTime, $isAvailable);
    
    if ($stmt->execute()) {
        json_response(true, "Schedule added successfully", ['id' => $conn->insert_id]);
    } else {
        json_response(false, "Failed to add schedule: " . $stmt->error);
    }
} else {
    json_response(false, "Invalid request method");
}
?>