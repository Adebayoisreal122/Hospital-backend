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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $name = $input['name'] ?? '';
    $email = $input['email'] ?? '';
    $phone = $input['phone'] ?? '';
    $specialization = $input['specialization'] ?? '';
    
    // Validation
    if (empty($name) || empty($email) || empty($phone) || empty($specialization)) {
        json_response(false, "All fields are required");
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_response(false, "Invalid email format");
    }
    
    // Check if email is taken by another doctor
    $stmt = $conn->prepare("SELECT id FROM doctors WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $doctorId);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        json_response(false, "Email is already taken by another account");
    }
    
    // Update profile
    $stmt = $conn->prepare("
        UPDATE doctors 
        SET name = ?, email = ?, phone = ?, specialization = ?
        WHERE id = ?
    ");
    
    $stmt->bind_param("ssssi", $name, $email, $phone, $specialization, $doctorId);
    
    if ($stmt->execute()) {
        json_response(true, "Profile updated successfully");
    } else {
        json_response(false, "Failed to update profile: " . $stmt->error);
    }
} else {
    json_response(false, "Invalid request method");
}
?>  