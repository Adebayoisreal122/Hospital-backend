<?php
/**
 * Get completed appointments that don't have invoices yet
 * Used when creating new invoices
 * Admin access only
 */
require_once __DIR__ . '/helpers.php';

// Require admin authentication
$info = require_admin($conn);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Get completed appointments without invoices
        $query = "
            SELECT 
                a.id,
                a.appointment_date,
                a.appointment_time,
                p.name as patient_name,
                p.id as patient_id,
                d.name as doctor_name,
                d.id as doctor_id,
                d.specialization
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            JOIN doctors d ON a.doctor_id = d.id
            LEFT JOIN invoices i ON a.id = i.appointment_id
            WHERE a.status = 'completed' AND i.id IS NULL
            ORDER BY a.appointment_date DESC
        ";
        
        $result = $conn->query($query);
        
        $appointments = [];
        while ($row = $result->fetch_assoc()) {
            $appointments[] = [
                'id' => (int)$row['id'],
                'appointment_date' => $row['appointment_date'],
                'appointment_time' => $row['appointment_time'],
                'patient_name' => $row['patient_name'],
                'patient_id' => (int)$row['patient_id'],
                'doctor_name' => $row['doctor_name'],
                'doctor_id' => (int)$row['doctor_id'],
                'specialization' => $row['specialization']
            ];
        }
        
        json_response(true, "Appointments retrieved successfully", $appointments);
        
    } catch (Exception $e) {
        json_response(false, "Error retrieving appointments: " . $e->getMessage());
    }
} else {
    json_response(false, "Invalid request method");
}
?>