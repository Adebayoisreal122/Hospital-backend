<?php
require_once __DIR__ . '/helpers.php';

$info = require_admin($conn);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $conn->query("SELECT COUNT(*) as total FROM doctors");
    $totalDoctors = $stmt->fetch_assoc()['total'];
    
    $stmt = $conn->query("SELECT COUNT(*) as total FROM patients");
    $totalPatients = $stmt->fetch_assoc()['total'];
    
    $stmt = $conn->query("SELECT COUNT(*) as total FROM appointments");
    $totalAppointments = $stmt->fetch_assoc()['total'];
    
    $stmt = $conn->query("SELECT COUNT(*) as total FROM appointments WHERE status = 'pending'");
    $pendingAppointments = $stmt->fetch_assoc()['total'];
    
    $stmt = $conn->query("
        SELECT 
            a.id,
            a.appointment_date,
            a.appointment_time,
            a.status,
            p.name as patient_name,
            d.name as doctor_name
        FROM appointments a
        JOIN patients p ON a.patient_id = p.id
        JOIN doctors d ON a.doctor_id = d.id
        ORDER BY a.created_at DESC
        LIMIT 10
    ");
    
    $recentAppointments = [];
    while ($row = $stmt->fetch_assoc()) {
        $recentAppointments[] = [
            'id' => (int)$row['id'],
            'appointment_date' => $row['appointment_date'],
            'appointment_time' => $row['appointment_time'],
            'status' => $row['status'],
            'patient_name' => $row['patient_name'],
            'doctor_name' => $row['doctor_name']
        ];
    }
    
    json_response(true, "Dashboard statistics retrieved", [
        'total_doctors' => (int)$totalDoctors,
        'total_patients' => (int)$totalPatients,
        'total_appointments' => (int)$totalAppointments,
        'pending_appointments' => (int)$pendingAppointments,
        'recent_appointments' => $recentAppointments
    ]);
} else {
    json_response(false, "Invalid request method");
}
?>