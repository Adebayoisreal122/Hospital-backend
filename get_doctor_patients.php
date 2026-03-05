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
        SELECT DISTINCT
            p.id as patient_id,
            p.name,
            p.email,
            p.phone,
            p.date_of_birth,
            p.gender,
            p.address,
            COUNT(a.id) as total_appointments,
            MAX(a.appointment_date) as last_visit
        FROM patients p
        INNER JOIN appointments a ON p.id = a.patient_id
        WHERE a.doctor_id = ?
        GROUP BY p.id
        ORDER BY last_visit DESC
    ");
    
    $stmt->bind_param("i", $doctorId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $patients = [];
    while ($row = $result->fetch_assoc()) {
        $patients[] = [
            'patient_id' => (int)$row['patient_id'],
            'name' => $row['name'],
            'email' => $row['email'],
            'phone' => $row['phone'],
            'date_of_birth' => $row['date_of_birth'],
            'gender' => $row['gender'],
            'address' => $row['address'],
            'total_appointments' => (int)$row['total_appointments'],
            'last_visit' => $row['last_visit']
        ];
    }
    
    json_response(true, "Patients retrieved successfully", $patients);
} else {
    json_response(false, "Invalid request method");
}
?>