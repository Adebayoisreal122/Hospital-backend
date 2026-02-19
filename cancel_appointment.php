<?php
session_start();
require_once __DIR__ . '/helpers.php';

// Check if patient is logged in
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
    $input = json_decode(file_get_contents('php://input'), true);
    $appointmentId = $input['appointment_id'] ?? 0;
    
    if (!$appointmentId) {
        json_response(false, "Appointment ID is required");
    }
    
    // Verify appointment belongs to this patient
    $stmt = $conn->prepare("SELECT id, status FROM appointments WHERE id = ? AND patient_id = ?");
    $stmt->bind_param("ii", $appointmentId, $patientId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        json_response(false, "Appointment not found");
    }
    
    $appointment = $result->fetch_assoc();
    
    if ($appointment['status'] === 'cancelled') {
        json_response(false, "Appointment is already cancelled");
    }
    
    if ($appointment['status'] === 'completed') {
        json_response(false, "Cannot cancel a completed appointment");
    }
    
    // Update appointment status
    $stmt = $conn->prepare("UPDATE appointments SET status = 'cancelled', updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $appointmentId);
    
    if ($stmt->execute()) {
        json_response(true, "Appointment cancelled successfully");
    } else {
        json_response(false, "Failed to cancel appointment");
    }
} else {
    json_response(false, "Invalid request method");
}
?>