<?php
session_start();
require_once __DIR__ . '/../includes/helpers.php';

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
    
    $appointmentId = $input['appointment_id'] ?? 0;
    $newStatus = $input['status'] ?? '';
    $notes = $input['notes'] ?? null;
    
    if (!$appointmentId || !$newStatus) {
        json_response(false, "Appointment ID and status are required");
    }
    
    // Validate status
    $validStatuses = ['pending', 'confirmed', 'completed', 'cancelled'];
    if (!in_array($newStatus, $validStatuses)) {
        json_response(false, "Invalid status");
    }
    
    // Verify appointment belongs to this doctor
    $stmt = $conn->prepare("SELECT id FROM appointments WHERE id = ? AND doctor_id = ?");
    $stmt->bind_param("ii", $appointmentId, $doctorId);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows === 0) {
        json_response(false, "Appointment not found");
    }
    
    // Update appointment
    if ($notes) {
        $stmt = $conn->prepare("UPDATE appointments SET status = ?, notes = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("ssi", $newStatus, $notes, $appointmentId);
    } else {
        $stmt = $conn->prepare("UPDATE appointments SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $newStatus, $appointmentId);
    }
    
    if ($stmt->execute()) {
        json_response(true, "Appointment updated successfully");
    } else {
        json_response(false, "Failed to update appointment");
    }
} else {
    json_response(false, "Invalid request method");
}
?>