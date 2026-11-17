<?php
require_once __DIR__ . '/../includes/helpers.php';

// Get authorization token - try multiple methods
$token = null;

// Method 1: From Authorization header
$headers = getallheaders();
if (isset($headers['Authorization'])) {
    $token = trim(str_replace('Bearer', '', $headers['Authorization']));
} elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $token = trim(str_replace('Bearer', '', $_SERVER['HTTP_AUTHORIZATION']));
}

// Debug logging
error_log("Book Appointment - Token: " . ($token ?: 'NO TOKEN'));

if (!$token) {
    json_response(false, "Unauthorized - No token provided");
}

$info = validate_token($conn, $token);

error_log("Token validation result: " . print_r($info, true));

if (!$info || $info['user_type'] !== 'patient') {
    json_response(false, "Unauthorized: patient access required");
}

$patientId = $info['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $doctorId = $input['doctor_id'] ?? 0;
    $appointmentDate = $input['appointment_date'] ?? '';
    $appointmentTime = $input['appointment_time'] ?? '';
    $reason = $input['reason'] ?? 'General consultation';
    
    if (!$doctorId || !$appointmentDate || !$appointmentTime) {
        json_response(false, "All fields are required");
    }
    
    $stmt = $conn->prepare("SELECT id FROM doctors WHERE id = ?");
    $stmt->bind_param("i", $doctorId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        json_response(false, "Invalid doctor selected");
    }
    
    $stmt = $conn->prepare("
        SELECT id FROM appointments 
        WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? 
        AND status != 'cancelled'
    ");
    $stmt->bind_param("iss", $doctorId, $appointmentDate, $appointmentTime);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        json_response(false, "This time slot is already booked. Please choose another time.");
    }
    
    $status = 'pending'; 
    $stmt = $conn->prepare("
        INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("iissss", $patientId, $doctorId, $appointmentDate, $appointmentTime, $reason, $status);
    
    if ($stmt->execute()) {
        json_response(true, "Appointment booked successfully", [
            'appointment_id' => $conn->insert_id,
            'status' => $status
        ]);
    } else {
        json_response(false, "Failed to book appointment: " . $stmt->error);
    }
} else {
    json_response(false, "Invalid request method");
}
?>