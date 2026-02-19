<?php
require_once __DIR__ . '/helpers.php';

// Require admin authentication
$info = require_admin($conn);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $patientId = $_GET['patient_id'] ?? 0;
    
    if (!$patientId) {
        json_response(false, "Patient ID is required");
    }
    
    // Get all appointments for this patient
    $stmt = $conn->prepare("
        SELECT 
            a.id,
            a.appointment_date,
            a.appointment_time,
            a.reason,
            a.status,
            a.notes,
            a.created_at,
            d.name as doctor_name,
            d.specialization
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.id
        WHERE a.patient_id = ?
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
    ");
    
    $stmt->bind_param("i", $patientId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $appointments = [];
    while ($row = $result->fetch_assoc()) {
        $appointments[] = [
            'id' => (int)$row['id'],
            'appointment_date' => $row['appointment_date'],
            'appointment_time' => $row['appointment_time'],
            'reason' => $row['reason'],
            'status' => $row['status'],
            'notes' => $row['notes'],
            'created_at' => $row['created_at'],
            'doctor_name' => $row['doctor_name'],
            'specialization' => $row['specialization']
        ];
    }
    
    json_response(true, "Patient history retrieved", $appointments);
} else {
    json_response(false, "Invalid request method");
}
?>