<?php
header("Content-Type: application/json");
include "config.php";
include "auth_check.php";

// Only Admins can create invoices, Doctors can see invoices for their patients, Patients can see their own invoices
require_role(['admin','doctor','patient']);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST' && $_SESSION['role'] === 'admin') {
    $input = json_decode(file_get_contents("php://input"), true);
    $patientId = $input['patient_id'] ?? null;
    $amount = $input['amount'] ?? null;
    $description = $input['description'] ?? '';

    if (!$patientId || !$amount) {
        echo json_encode(["status"=>false,"message"=>"Missing invoice data"]);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO invoices (patient_id, amount, description, status) VALUES (?, ?, ?, 'unpaid')");
    $stmt->bind_param("ids", $patientId, $amount, $description);

    if ($stmt->execute()) {
        echo json_encode(["status"=>true,"message"=>"Invoice created","invoice_id"=>$stmt->insert_id]);
    } else {
        echo json_encode(["status"=>false,"message"=>"Error: ".$conn->error]);
    }
    exit;
}

if ($method === 'GET') {
    if ($_SESSION['role'] === 'admin') {
        $res = $conn->query("SELECT i.id,i.amount,i.status,i.description,p.name AS patient 
            FROM invoices i 
            JOIN patients p ON i.patient_id=p.id");
        echo json_encode(["status"=>true,"invoices"=>$res->fetch_all(MYSQLI_ASSOC)]);
    } elseif ($_SESSION['role'] === 'doctor') {
        $doctorId = $_SESSION['user_id'];
        $res = $conn->query("SELECT i.id,i.amount,i.status,i.description,p.name AS patient 
            FROM invoices i
            JOIN patients p ON i.patient_id=p.id
            WHERE p.assigned_doctor_id=$doctorId");
        echo json_encode(["status"=>true,"invoices"=>$res->fetch_all(MYSQLI_ASSOC)]);
    } elseif ($_SESSION['role'] === 'patient') {
        $patientId = $_SESSION['user_id'];
        $res = $conn->query("SELECT * FROM invoices WHERE patient_id=$patientId");
        echo json_encode(["status"=>true,"invoices"=>$res->fetch_all(MYSQLI_ASSOC)]);
    }
    exit;
}

echo json_encode(["status"=>false,"message"=>"Invalid request"]);
