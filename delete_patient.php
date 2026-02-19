<?php
require_once __DIR__ . '/helpers.php';

// Require admin authentication
$info = require_admin($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $patientId = $input['patient_id'] ?? 0;
    
    if (!$patientId) {
        json_response(false, "Patient ID is required");
    }
    
    // Check if patient exists
    $stmt = $conn->prepare("SELECT id FROM patients WHERE id = ?");
    $stmt->bind_param("i", $patientId);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows === 0) {
        json_response(false, "Patient not found");
    }
    
    // Cancel all future appointments
    $stmt = $conn->prepare("
        UPDATE appointments 
        SET status = 'cancelled' 
        WHERE patient_id = ? AND appointment_date >= CURDATE() AND status != 'completed'
    ");
    $stmt->bind_param("i", $patientId);
    $stmt->execute();
    
    // Delete auth tokens
    $stmt = $conn->prepare("DELETE FROM auth_tokens WHERE user_type = 'patient' AND user_id = ?");
    $stmt->bind_param("i", $patientId);
    $stmt->execute();
    
    // Delete patient
    $stmt = $conn->prepare("DELETE FROM patients WHERE id = ?");
    $stmt->bind_param("i", $patientId);
    
    if ($stmt->execute()) {
        json_response(true, "Patient deleted successfully");
    } else {
        json_response(false, "Failed to delete patient: " . $stmt->error);
    }
} else {
    json_response(false, "Invalid request method");
}
?>