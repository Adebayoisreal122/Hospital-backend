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
    $stmt = $conn->prepare("
        SELECT 
            a.id,
            a.appointment_date,
            a.appointment_time,
            a.reason,
            a.status,
            a.notes,
            a.created_at,
            p.name as patient_name,
            p.email as patient_email,
            p.phone as patient_phone,
            p.gender as patient_gender,
            p.date_of_birth as patient_dob
        FROM appointments a
        JOIN patients p ON a.patient_id = p.id
        WHERE a.doctor_id = ?
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
    ");
    
    $stmt->bind_param("i", $doctorId);
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
            'patient_name' => $row['patient_name'],
            'patient_email' => $row['patient_email'],
            'patient_phone' => $row['patient_phone'],
            'patient_gender' => $row['patient_gender'],
            'patient_dob' => $row['patient_dob']
        ];
    }
    
    json_response(true, "Appointments retrieved successfully", $appointments);
} else {
    json_response(false, "Invalid request method");
}
?>