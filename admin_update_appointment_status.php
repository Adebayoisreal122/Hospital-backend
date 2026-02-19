<?php
require_once __DIR__ . '/helpers.php';

// Require admin authentication
$info = require_admin($conn);

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
    
    // Check if appointment exists
    $stmt = $conn->prepare("SELECT id FROM appointments WHERE id = ?");
    $stmt->bind_param("i", $appointmentId);
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