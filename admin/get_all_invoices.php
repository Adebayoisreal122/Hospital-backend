<?php

require_once __DIR__ . '/../includes/helpers.php';

$info = require_admin($conn);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $query = "
            SELECT 
                i.id,
                i.appointment_id,
                i.amount,
                i.status,
                i.payment_method,
                i.payment_date,
                i.notes,
                i.created_at,
                i.updated_at,
                p.name as patient_name,
                p.email as patient_email,
                d.name as doctor_name,
                d.specialization,
                a.appointment_date,
                a.appointment_time
            FROM invoices i
            JOIN appointments a ON i.appointment_id = a.id
            JOIN patients p ON i.patient_id = p.id
            JOIN doctors d ON i.doctor_id = d.id
            ORDER BY i.created_at DESC
        ";
        
        $result = $conn->query($query);
        
        $invoices = [];
        while ($row = $result->fetch_assoc()) {
            $invoices[] = [
                'id' => (int)$row['id'],
                'appointment_id' => (int)$row['appointment_id'],
                'amount' => (float)$row['amount'],
                'status' => $row['status'],
                'payment_method' => $row['payment_method'],
                'payment_date' => $row['payment_date'],
                'notes' => $row['notes'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],
                'patient_name' => $row['patient_name'],
                'patient_email' => $row['patient_email'],
                'doctor_name' => $row['doctor_name'],
                'specialization' => $row['specialization'],
                'appointment_date' => $row['appointment_date'],
                'appointment_time' => $row['appointment_time']
            ];
        }
        
        json_response(true, "Invoices retrieved successfully", $invoices);
        
    } catch (Exception $e) {
        json_response(false, "Error retrieving invoices: " . $e->getMessage());
    }
} else {
    json_response(false, "Invalid request method");
}
?>