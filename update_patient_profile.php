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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $name = $input['name'] ?? '';
    $email = $input['email'] ?? '';
    $phone = $input['phone'] ?? '';
    $date_of_birth = $input['date_of_birth'] ?? null;
    $gender = $input['gender'] ?? '';
    $address = $input['address'] ?? '';
    
    // Validation
    if (empty($name) || empty($email) || empty($phone)) {
        json_response(false, "Name, email, and phone are required");
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_response(false, "Invalid email format");
    }
    
    // Check if email is taken by another patient
    $stmt = $conn->prepare("SELECT id FROM patients WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $patientId);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        json_response(false, "Email is already taken by another account");
    }
    
    // Update profile
    $stmt = $conn->prepare("
        UPDATE patients 
        SET name = ?, email = ?, phone = ?, date_of_birth = ?, gender = ?, address = ?, updated_at = NOW()
        WHERE id = ?
    ");
    
    $stmt->bind_param("ssssssi", $name, $email, $phone, $date_of_birth, $gender, $address, $patientId);
    
    if ($stmt->execute()) {
        json_response(true, "Profile updated successfully");
    } else {
        json_response(false, "Failed to update profile: " . $stmt->error);
    }
} else {
    json_response(false, "Invalid request method");
}
?>