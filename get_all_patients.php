<?php
require_once __DIR__ . '/helpers.php';

$info = require_admin($conn);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $conn->query("
        SELECT 
            id,
            name,
            email,
            phone,
            date_of_birth,
            gender,
            address,
            created_at
        FROM patients
        ORDER BY created_at DESC
    ");
    
    $patients = [];
    while ($row = $stmt->fetch_assoc()) {
        $patients[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'email' => $row['email'],
            'phone' => $row['phone'],
            'date_of_birth' => $row['date_of_birth'],
            'gender' => $row['gender'],
            'address' => $row['address'],
            'created_at' => $row['created_at']
        ];
    }
    
    json_response(true, "Patients retrieved successfully", $patients);
} else {
    json_response(false, "Invalid request method");
}
?>