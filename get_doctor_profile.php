<?php
session_start();
require_once __DIR__ . '/helpers.php';

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
        SELECT id, name, email, phone, specialization, created_at
        FROM doctors
        WHERE id = ?
    ");
    
    $stmt->bind_param("i", $doctorId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        json_response(true, "Profile retrieved successfully", [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'email' => $row['email'],
            'phone' => $row['phone'],
            'specialization' => $row['specialization'],
            'created_at' => $row['created_at']
        ]);
    } else {
        json_response(false, "Profile not found");
    }
} else {
    json_response(false, "Invalid request method");
}
?>