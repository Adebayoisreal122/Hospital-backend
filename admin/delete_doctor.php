<?php
require_once __DIR__ . '/../includes/helpers.php';

$info = require_admin($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $doctorId = $input['doctor_id'] ?? 0;
    
    if (!$doctorId) {
        json_response(false, "Doctor ID is required");
    }
    
    $stmt = $conn->prepare("SELECT id FROM doctors WHERE id = ?");
    $stmt->bind_param("i", $doctorId);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows === 0) {
        json_response(false, "Doctor not found");
    }
    
    // Cancel all future appointments
    $stmt = $conn->prepare("
        UPDATE appointments 
        SET status = 'cancelled' 
        WHERE doctor_id = ? AND appointment_date >= CURDATE() AND status != 'completed'
    ");
    $stmt->bind_param("i", $doctorId);
    $stmt->execute();
    
    $stmt = $conn->prepare("DELETE FROM doctor_schedules WHERE doctor_id = ?");
    $stmt->bind_param("i", $doctorId);
    $stmt->execute();
    
    $stmt = $conn->prepare("DELETE FROM auth_tokens WHERE user_type = 'doctor' AND user_id = ?");
    $stmt->bind_param("i", $doctorId);
    $stmt->execute();
    
    $stmt = $conn->prepare("DELETE FROM doctors WHERE id = ?");
    $stmt->bind_param("i", $doctorId);
    
    if ($stmt->execute()) {
        json_response(true, "Doctor deleted successfully");
    } else {
        json_response(false, "Failed to delete doctor: " . $stmt->error);
    }
} else {
    json_response(false, "Invalid request method");
}
?>