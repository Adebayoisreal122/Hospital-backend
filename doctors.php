<?php
header("Content-Type: application/json");
include "config.php";
include "auth_check.php";

require_role(['admin','doctor']);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    if ($_SESSION['role'] === 'admin') {
        $res = $conn->query("SELECT id, name, specialization, email, phone, created_at FROM doctors");
        echo json_encode(["status"=>true,"doctors"=>$res->fetch_all(MYSQLI_ASSOC)]);
    } elseif ($_SESSION['role'] === 'doctor') {
        $doctorId = $_SESSION['user_id'];
        $stmt = $conn->prepare("SELECT id, name, specialization, email, phone, created_at 
                                FROM doctors WHERE id=?");
        $stmt->bind_param("i", $doctorId);
        $stmt->execute();
        $result = $stmt->get_result();
        echo json_encode(["status"=>true,"doctor"=>$result->fetch_assoc()]);
    }
    exit;
}

if ($method === 'POST' && $_SESSION['role'] === 'admin') {
    $input = json_decode(file_get_contents("php://input"), true);
    $name = $input['name'] ?? null;
    $specialization = $input['specialization'] ?? null;
    $email = $input['email'] ?? null;
    $phone = $input['phone'] ?? null;
    $password = $input['password'] ?? null;

    if (!$name || !$specialization || !$email || !$phone || !$password) {
        echo json_encode(["status"=>false,"message"=>"Missing fields"]);
        exit;
    }

    $hashed = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $conn->prepare("INSERT INTO doctors (name, specialization, email, phone, password) 
                            VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $specialization, $email, $phone, $hashed);

    if ($stmt->execute()) {
        echo json_encode(["status"=>true,"message"=>"Doctor created","doctor_id"=>$stmt->insert_id]);
    } else {
        echo json_encode(["status"=>false,"message"=>"Error: ".$conn->error]);
    }
    exit;
}

if ($method === 'PUT') {
    parse_str(file_get_contents("php://input"), $_PUT);
    $name = $_PUT['name'] ?? null;
    $specialization = $_PUT['specialization'] ?? null;
    $email = $_PUT['email'] ?? null;
    $phone = $_PUT['phone'] ?? null;

    if (!$name || !$specialization || !$email || !$phone) {
        echo json_encode(["status"=>false,"message"=>"Missing fields"]);
        exit;
    }

    if ($_SESSION['role'] === 'admin') {
        $id = $_PUT['id'] ?? null;
        if (!$id) {
            echo json_encode(["status"=>false,"message"=>"Doctor ID required"]);
            exit;
        }
        $stmt = $conn->prepare("UPDATE doctors SET name=?, specialization=?, email=?, phone=? WHERE id=?");
        $stmt->bind_param("ssssi", $name, $specialization, $email, $phone, $id);
    } elseif ($_SESSION['role'] === 'doctor') {
        $id = $_SESSION['user_id'];
        $stmt = $conn->prepare("UPDATE doctors SET name=?, specialization=?, email=?, phone=? WHERE id=?");
        $stmt->bind_param("ssssi", $name, $specialization, $email, $phone, $id);
    }

    if ($stmt->execute()) {
        echo json_encode(["status"=>true,"message"=>"Doctor updated"]);
    } else {
        echo json_encode(["status"=>false,"message"=>"Update failed: ".$conn->error]);
    }
    exit;
}

if ($method === 'DELETE' && $_SESSION['role'] === 'admin') {
    parse_str(file_get_contents("php://input"), $_DELETE);
    $id = $_DELETE['id'] ?? null;

    if (!$id) {
        echo json_encode(["status"=>false,"message"=>"Doctor ID required"]);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM doctors WHERE id=?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(["status"=>true,"message"=>"Doctor deleted"]);
    } else {
        echo json_encode(["status"=>false,"message"=>"Delete failed: ".$conn->error]);
    }
    exit;
}

echo json_encode(["status"=>false,"message"=>"Invalid request"]);
