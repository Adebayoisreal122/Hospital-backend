<?php
require_once __DIR__ . '/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Get all active doctors
    $stmt = $conn->prepare("
        SELECT id, name, email, phone, specialization, created_at
        FROM doctors
        ORDER BY name ASC
    ");
    
    $stmt->execute();
    $result = $stmt->get_result();
    $doctors = [];
    
    while ($row = $result->fetch_assoc()) {
        $doctors[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'email' => $row['email'],
            'phone' => $row['phone'],
            'specialization' => $row['specialization'],
            'created_at' => $row['created_at']
        ];
    }
    
    json_response(true, "Doctors retrieved successfully", $doctors);
} else {
    json_response(false, "Invalid request method");
}
?>