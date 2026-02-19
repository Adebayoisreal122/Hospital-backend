<?php
/**
 * Create a new invoice for a completed appointment
 * Admin access only
 */
require_once __DIR__ . '/helpers.php';

// Require admin authentication
$info = require_admin($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get input data
        $input = json_decode(file_get_contents('php://input'), true);
        
        $appointmentId = $input['appointment_id'] ?? 0;
        $amount = $input['amount'] ?? 0;
        $notes = $input['notes'] ?? '';
        
        // Validation
        if (!$appointmentId) {
            json_response(false, "Appointment ID is required");
        }
        
        if ($amount <= 0) {
            json_response(false, "Amount must be greater than 0");
        }
        
        // Get appointment details
        $stmt = $conn->prepare("
            SELECT patient_id, doctor_id, status 
            FROM appointments 
            WHERE id = ?
        ");
        $stmt->bind_param("i", $appointmentId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            json_response(false, "Appointment not found");
        }
        
        $appointment = $result->fetch_assoc();
        
        // Check if appointment is completed
        if ($appointment['status'] !== 'completed') {
            json_response(false, "Only completed appointments can have invoices");
        }
        
        // Check if invoice already exists for this appointment
        $stmt = $conn->prepare("SELECT id FROM invoices WHERE appointment_id = ?");
        $stmt->bind_param("i", $appointmentId);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            json_response(false, "Invoice already exists for this appointment");
        }
        
        // Create invoice
        $stmt = $conn->prepare("
            INSERT INTO invoices 
            (appointment_id, patient_id, doctor_id, amount, notes, status, created_at)
            VALUES (?, ?, ?, ?, ?, 'pending', NOW())
        ");
        
        $stmt->bind_param(
            "iiids",
            $appointmentId,
            $appointment['patient_id'],
            $appointment['doctor_id'],
            $amount,
            $notes
        );
        
        if ($stmt->execute()) {
            $invoiceId = $conn->insert_id;
            json_response(true, "Invoice created successfully", [
                'invoice_id' => $invoiceId
            ]);
        } else {
            json_response(false, "Failed to create invoice: " . $stmt->error);
        }
        
    } catch (Exception $e) {
        json_response(false, "Error creating invoice: " . $e->getMessage());
    }
} else {
    json_response(false, "Invalid request method");
}
?>