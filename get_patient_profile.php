<?php
session_start();
require_once __DIR__ . '/helpers.php';

// Check if patient is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
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
            json_response(false, "Unauthorized");
        }
        $patientId = $info['user_id'];
    } else {
        json_response(false, "Unauthorized");
    }
} else {
    $patientId = $_SESSION['user_id'];
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $conn->prepare("
        SELECT id, name, email, phone, date_of_birth, gender, address, created_at
        FROM patients
        WHERE id = ?
    ");
    
    $stmt->bind_param("i", $patientId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        json_response(true, "Profile retrieved successfully", [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'email' => $row['email'],
            'phone' => $row['phone'],
            'date_of_birth' => $row['date_of_birth'],
            'gender' => $row['gender'],
            'address' => $row['address'],
            'created_at' => $row['created_at']
        ]);
    } else {
        json_response(false, "Profile not found");
    }
} else {
    json_response(false, "Invalid request method");
}
?>