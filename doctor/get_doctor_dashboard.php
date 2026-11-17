<?php
session_start();
require_once __DIR__ . '/../includes/helpers.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    $token = null;
    $headers = getallheaders();
    
    if (isset($headers['Authorization'])) {
        $token = trim(str_replace('Bearer', '', $headers['Authorization']));
    } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $token = trim(str_replace('Bearer', '', $_SERVER['HTTP_AUTHORIZATION']));
    }
    
    if ($token) {
        $info = validate_token($conn, $token);
        if (!$info || $info['user_type'] !== 'doctor') {
            json_response(false, "Unauthorized");
        }
        $doctorId = $info['user_id'];
    } else {
        json_response(false, "Unauthorized");
    }
} else {
    $doctorId = $_SESSION['user_id'];
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT patient_id) as total_patients
        FROM appointments
        WHERE doctor_id = ?
    ");
    $stmt->bind_param("i", $doctorId);
    $stmt->execute();
    $totalPatients = $stmt->get_result()->fetch_assoc()['total_patients'];
    
    $stmt = $conn->prepare("
        SELECT COUNT(*) as upcoming
        FROM appointments
        WHERE doctor_id = ? 
        AND status IN ('confirmed', 'pending')
        AND appointment_date >= CURDATE()
    ");
    $stmt->bind_param("i", $doctorId);
    $stmt->execute();
    $upcomingAppointments = $stmt->get_result()->fetch_assoc()['upcoming'];
    
    $stmt = $conn->prepare("
        SELECT COUNT(*) as completed
        FROM appointments
        WHERE doctor_id = ? 
        AND status = 'completed'
    ");
    $stmt->bind_param("i", $doctorId);
    $stmt->execute();
    $completedConsultations = $stmt->get_result()->fetch_assoc()['completed'];
    
    $stmt = $conn->prepare("
        SELECT COUNT(*) as pending
        FROM appointments
        WHERE doctor_id = ? 
        AND status = 'pending'
    ");
    $stmt->bind_param("i", $doctorId);
    $stmt->execute();
    $pendingAppointments = $stmt->get_result()->fetch_assoc()['pending'];
    
    json_response(true, "Dashboard data retrieved", [
        'total_patients' => (int)$totalPatients,
        'upcoming_appointments' => (int)$upcomingAppointments,
        'completed_consultations' => (int)$completedConsultations,
        'pending_appointments' => (int)$pendingAppointments
    ]);
} else {
    json_response(false, "Invalid request method");
}
?>