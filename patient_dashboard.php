<?php
require_once __DIR__ . '/helpers.php';

$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = str_replace('Bearer ', '', $authHeader);

if (!$token) {
    json_response(false, "Unauthorized");
}

$info = validate_token($conn, $token);

if (!$info || $info['user_type'] !== 'patient') {
    json_response(false, "Unauthorized: patient access required");
}

$patientId = $info['user_id'];

$nextAppointment = null;
$stmt = $conn->prepare("
    SELECT a.id, a.appointment_date, a.appointment_time, d.name as doctor_name, d.specialization
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.id
    WHERE a.patient_id = ? AND a.status = 'confirmed' AND a.appointment_date >= CURDATE()
    ORDER BY a.appointment_date ASC, a.appointment_time ASC
    LIMIT 1
");
$stmt->bind_param("i", $patientId);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $nextAppointment = [
        'id' => $row['id'],
        'date' => $row['appointment_date'],
        'time' => $row['appointment_time'],
        'doctor_name' => $row['doctor_name'],
        'specialization' => $row['specialization']
    ];
}

$stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM appointments 
    WHERE patient_id = ? AND status = 'confirmed' AND appointment_date >= CURDATE()
");
$stmt->bind_param("i", $patientId);
$stmt->execute();
$result = $stmt->get_result();
$upcomingCount = $result->fetch_assoc()['count'];

$stmt = $conn->prepare("
    SELECT d.name, COUNT(*) as visit_count
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.id
    WHERE a.patient_id = ?
    GROUP BY a.doctor_id
    ORDER BY visit_count DESC
    LIMIT 1
");
$stmt->bind_param("i", $patientId);
$stmt->execute();
$result = $stmt->get_result();
$assignedDoctor = null;
if ($row = $result->fetch_assoc()) {
    $assignedDoctor = $row['name'];
}

json_response(true, "Dashboard data retrieved", [
    'next_appointment' => $nextAppointment,
    'upcoming_count' => (int)$upcomingCount,
    'assigned_doctor' => $assignedDoctor
]);
?>