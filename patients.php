<?php
header("Content-Type: application/json");
include "config.php";
include "auth_check.php";

// Admin can manage all patients, doctors see their assigned patients, patients see only themselves
require_role(['admin','doctor','patient']);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST' && $_SESSION['role'] === 'admin') {
    // Admin creates a new patient
    $input = json_decode(file_get_contents("php://input"), true);
    $name = $input['name'] ?? '';
    $age = $input['age'] ?? null;
    $gender = $input['gender'] ?? 'male';
    $illness_category = $input['illness_category'] ?? '';
    $email = $input['email'] ?? '';
    $phone = $input['phone'] ?? '';
    $password = $input['password'] ?? null;

    if (!$name || !$illness_category) {
        echo json_encode(["status"=>false,"message"=>"Missing patient data"]);
        exit;
    }

    $hashed = $password ? password_hash($password, PASSWORD_BCRYPT) : null;
    $stmt = $conn->prepare("INSERT INTO patients (name, age, gender, illness_category, email, phone, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sisssss", $name, $age, $gender, $illness_category, $email, $phone, $hashed);

    if ($stmt->execute()) {
        echo json_encode(["status"=>true,"message"=>"Patient created","patient_id"=>$stmt->insert_id]);
    } else {
        echo json_encode(["status"=>false,"message"=>"Error: ".$conn->error]);
    }
    exit;
}

if ($method === 'GET') {
    if ($_SESSION['role'] === 'admin') {
        // Admin: list all patients
        $res = $conn->query("SELECT * FROM patients ORDER BY created_at DESC");
        echo json_encode(["status"=>true,"patients"=>$res->fetch_all(MYSQLI_ASSOC)]);
    } elseif ($_SESSION['role'] === 'doctor') {
        // Doctor: only see their patients
        $doctorId = $_SESSION['user_id'];
        $res = $conn->query("SELECT * FROM patients WHERE assigned_doctor_id=$doctorId");
        echo json_encode(["status"=>true,"patients"=>$res->fetch_all(MYSQLI_ASSOC)]);
    } elseif ($_SESSION['role'] === 'patient') {
        // Patient: only see themselves
        $patientId = $_SESSION['user_id'];
        $res = $conn->query("SELECT * FROM patients WHERE id=$patientId");
        echo json_encode(["status"=>true,"patients"=>$res->fetch_assoc()]);
    }
    exit;
}

echo json_encode(["status"=>false,"message"=>"Invalid request"]);
