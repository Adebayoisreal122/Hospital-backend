<?php
require_once __DIR__ . '/../includes/helpers.php';

$info = require_admin($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $appointmentId = $input['appointment_id'] ?? 0;
    
    if (!$appointmentId) {
        json_response(false, "Appointment ID is required");
    }
    
    $stmt = $conn->prepare("SELECT id FROM appointments WHERE id = ?");
    $stmt->bind_param("i", $appointmentId);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows === 0) {
        json_response(false, "Appointment not found");
    }
    
    $stmt = $conn->prepare("DELETE FROM appointments WHERE id = ?");
    $stmt->bind_param("i", $appointmentId);
    
    if ($stmt->execute()) {
        json_response(true, "Appointment deleted successfully");
    } else {
        json_response(false, "Failed to delete appointment");
    }
} else {
    json_response(false, "Invalid request method");
}
?>