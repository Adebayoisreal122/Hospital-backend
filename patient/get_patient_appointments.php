<?php
session_start();
require_once __DIR__ . '/../includes/helpers.php';

// Check if patient is logged in via session
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    // If no session, try token
    $token = null;
    $headers = getallheaders();
    
    if (isset($headers['Authorization'])) {
        $token = trim(str_replace('Bearer', '', $headers['Authorization']));
    } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $token = trim(str_replace('Bearer', '', $_SERVER['HTTP_AUTHORIZATION']));
    }
    
    if ($token) {
        $info = validate_token($conn, $token);
        if (!$info || $info['user_type'] !== 'patient') {
            json_response(false, "Unauthorized: patient access required");
        }
        $patientId = $info['user_id'];
    } else {
        json_response(false, "Unauthorized - Please login");
    }
} else {
    $patientId = $_SESSION['user_id'];
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
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
            d.specialization,
            d.email as doctor_email,
            d.phone as doctor_phone
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
            'specialization' => $row['specialization'],
            'doctor_email' => $row['doctor_email'],
            'doctor_phone' => $row['doctor_phone']
        ];
    }
    
    json_response(true, "Appointments retrieved successfully", $appointments);
} else {
    json_response(false, "Invalid request method");
}
?>