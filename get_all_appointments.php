<?php
require_once __DIR__ . '/helpers.php';

// Require admin authentication
$info = require_admin($conn);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get all appointments with patient and doctor info
    $stmt = $conn->query("
        SELECT 
            a.id,
            a.appointment_date,
            a.appointment_time,
            a.reason,
            a.status,
            a.notes,
            a.created_at,
            a.doctor_id,
            a.patient_id,
            p.name as patient_name,
            p.email as patient_email,
            p.phone as patient_phone,
            d.name as doctor_name,
            d.specialization
        FROM appointments a
        JOIN patients p ON a.patient_id = p.id
        JOIN doctors d ON a.doctor_id = d.id
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
    ");
    
    $appointments = [];
    while ($row = $stmt->fetch_assoc()) {
        $appointments[] = [
            'id' => (int)$row['id'],
            'appointment_date' => $row['appointment_date'],
            'appointment_time' => $row['appointment_time'],
            'reason' => $row['reason'],
            'status' => $row['status'],
            'notes' => $row['notes'],
            'created_at' => $row['created_at'],
            'doctor_id' => (int)$row['doctor_id'],
            'patient_id' => (int)$row['patient_id'],
            'patient_name' => $row['patient_name'],
            'patient_email' => $row['patient_email'],
            'patient_phone' => $row['patient_phone'],
            'doctor_name' => $row['doctor_name'],
            'specialization' => $row['specialization']
        ];
    }
    
    json_response(true, "Appointments retrieved successfully", $appointments);
} else {
    json_response(false, "Invalid request method");
}
?>