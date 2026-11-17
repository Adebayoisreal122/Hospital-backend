<?php
require_once __DIR__ . '/../includes/helpers.php';

// Require admin authentication
$info = require_admin($conn);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $doctorId = $_GET['doctor_id'] ?? 0;
    
    if (!$doctorId) {
        json_response(false, "Doctor ID is required");
    }
    
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT patient_id) as total_patients
        FROM appointments
        WHERE doctor_id = ?
    ");
    $stmt->bind_param("i", $doctorId);
    $stmt->execute();
    $totalPatients = $stmt->get_result()->fetch_assoc()['total_patients'];
    
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total_appointments
        FROM appointments
        WHERE doctor_id = ?
    ");
    $stmt->bind_param("i", $doctorId);
    $stmt->execute();
    $totalAppointments = $stmt->get_result()->fetch_assoc()['total_appointments'];
    
    $stmt = $conn->prepare("
        SELECT COUNT(*) as pending
        FROM appointments
        WHERE doctor_id = ? AND status = 'pending'
    ");
    $stmt->bind_param("i", $doctorId);
    $stmt->execute();
    $pendingAppointments = $stmt->get_result()->fetch_assoc()['pending'];
    
    $stmt = $conn->prepare("
        SELECT COUNT(*) as completed
        FROM appointments
        WHERE doctor_id = ? AND status = 'completed'
    ");
    $stmt->bind_param("i", $doctorId);
    $stmt->execute();
    $completedAppointments = $stmt->get_result()->fetch_assoc()['completed'];
    
    json_response(true, "Doctor statistics retrieved", [
        'total_patients' => (int)$totalPatients,
        'total_appointments' => (int)$totalAppointments,
        'pending_appointments' => (int)$pendingAppointments,
        'completed_appointments' => (int)$completedAppointments
    ]);
} else {
    json_response(false, "Invalid request method");
}
?>