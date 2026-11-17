<?php
// admin_register.php
session_start();

header('Access-Control-Allow-Origin: http://localhost:4200');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/config.php'; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $name = $input['name'] ?? $_POST['name'] ?? '';
    $email = $input['email'] ?? $_POST['email'] ?? '';
    $password = $input['password'] ?? $_POST['password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        echo json_encode(["status"=>false,"message"=>"All fields are required","data"=>null]);
        exit;
    }

    $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM admins");
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $adminCount = $result->fetch_assoc()['count'];

  
    $emailCheckStmt = $conn->prepare("SELECT id FROM admins WHERE email = ?");
    $emailCheckStmt->bind_param("s", $email);
    $emailCheckStmt->execute();
    $emailResult = $emailCheckStmt->get_result();
    
    if ($emailResult->num_rows > 0) {
        echo json_encode(["status"=>false,"message"=>"Admin with this email already exists","data"=>null]);
        exit;
    }

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $conn->prepare("INSERT INTO admins (name, email, password, role) VALUES (?, ?, ?, ?)");
    $role = "admin";
    $stmt->bind_param("ssss", $name, $email, $hashedPassword, $role);

    if ($stmt->execute()) {
        echo json_encode([
            "status"=>true,
            "message"=>"Admin registered successfully",
            "data"=>["id"=>$conn->insert_id]
        ]);
    } else {
        echo json_encode([
            "status"=>false,
            "message"=>"Database error: ".$conn->error,
            "data"=>null
        ]);
    }

    $stmt->close();
    $emailCheckStmt->close();
    $checkStmt->close();
} else {
    echo json_encode(["status"=>false,"message"=>"Only POST method allowed","data"=>null]);
}
?>