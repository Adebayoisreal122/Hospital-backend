<?php
session_start();
require_once __DIR__ . '/helpers.php';

// Check if doctor is logged in
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
    $patientId = $_GET['patient_id'] ?? 0;
    
    if (!$patientId) {
        json_response(false, "Patient ID is required");
    }
    
    // Get all appointments for this patient with this doctor
    $stmt = $conn->prepare("
        SELECT 
            id,
            appointment_date,
            appointment_time,
            reason,
            status,
            notes,
            created_at
        FROM appointments
        WHERE patient_id = ? AND doctor_id = ?
        ORDER BY appointment_date DESC, appointment_time DESC
    ");
    
    $stmt->bind_param("ii", $patientId, $doctorId);
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
            'created_at' => $row['created_at']
        ];
    }
    
    json_response(true, "Appointment history retrieved", $appointments);
} else {
    json_response(false, "Invalid request method");
}
?>