<?php

require_once __DIR__ . '/../includes/helpers.php';

$info = require_admin($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $invoiceId = $input['invoice_id'] ?? 0;
        
        if (!$invoiceId) {
            json_response(false, "Invoice ID is required");
        }
        
        $stmt = $conn->prepare("SELECT id FROM invoices WHERE id = ?");
        $stmt->bind_param("i", $invoiceId);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows === 0) {
            json_response(false, "Invoice not found");
        }
        
        $stmt = $conn->prepare("DELETE FROM invoices WHERE id = ?");
        $stmt->bind_param("i", $invoiceId);
        
        if ($stmt->execute()) {
            json_response(true, "Invoice deleted successfully");
        } else {
            json_response(false, "Failed to delete invoice: " . $stmt->error);
        }
        
    } catch (Exception $e) {
        json_response(false, "Error deleting invoice: " . $e->getMessage());
    }
} else {
    json_response(false, "Invalid request method");
}
?>