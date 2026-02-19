<?php
header("Content-Type: application/json");
include "config.php";
include "auth_check.php";

// Admin: full control, Doctors: see their appointments, Patients: see their own
require_role(['admin','doctor','patient']);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST' && $_SESSION['role'] === 'admin') {
    // Admin schedules appointment
    $input = json_decode(file_get_contents("php://input"), true);
    $patientId = $input['patient_id'] ?? null;
    $doctorId = $input['doctor_id'] ?? null;
    $date = $input['appointment_date'] ?? null;

    if (!$patientId || !$doctorId || !$date) {
        echo json_encode(["status"=>false,"message"=>"Missing appointment data"]);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, status) VALUES (?, ?, ?, 'scheduled')");
    $stmt->bind_param("iis", $patientId, $doctorId, $date);

    if ($stmt->execute()) {
        echo json_encode(["status"=>true,"message"=>"Appointment created","appointment_id"=>$stmt->insert_id]);
    } else {
        echo json_encode(["status"=>false,"message"=>"Error: ".$conn->error]);
    }
    exit;
}

if ($method === 'GET') {
    if ($_SESSION['role'] === 'admin') {
        $res = $conn->query("SELECT a.id,a.appointment_date,a.status,p.name AS patient,d.name AS doctor 
            FROM appointments a
            JOIN patients p ON a.patient_id=p.id
            JOIN doctors d ON a.doctor_id=d.id
            ORDER BY a.appointment_date DESC");
        echo json_encode(["status"=>true,"appointments"=>$res->fetch_all(MYSQLI_ASSOC)]);
    } elseif ($_SESSION['role'] === 'doctor') {
        $doctorId = $_SESSION['user_id'];
        $res = $conn->query("SELECT a.id,a.appointment_date,a.status,p.name AS patient 
            FROM appointments a
            JOIN patients p ON a.patient_id=p.id
            WHERE a.doctor_id=$doctorId");
        echo json_encode(["status"=>true,"appointments"=>$res->fetch_all(MYSQLI_ASSOC)]);
    } elseif ($_SESSION['role'] === 'patient') {
        $patientId = $_SESSION['user_id'];
        $res = $conn->query("SELECT a.id,a.appointment_date,a.status,d.name AS doctor 
            FROM appointments a
            JOIN doctors d ON a.doctor_id=d.id
            WHERE a.patient_id=$patientId");
        echo json_encode(["status"=>true,"appointments"=>$res->fetch_all(MYSQLI_ASSOC)]);
    }
    exit;
}

echo json_encode(["status"=>false,"message"=>"Invalid request"]);
