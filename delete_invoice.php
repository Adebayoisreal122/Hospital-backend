<?php
/**
 * Permanently delete an invoice
 * Admin access only
 */
require_once __DIR__ . '/helpers.php';

// Require admin authentication
$info = require_admin($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get input data
        $input = json_decode(file_get_contents('php://input'), true);
        $invoiceId = $input['invoice_id'] ?? 0;
        
        // Validation
        if (!$invoiceId) {
            json_response(false, "Invoice ID is required");
        }
        
        // Check if invoice exists
        $stmt = $conn->prepare("SELECT id FROM invoices WHERE id = ?");
        $stmt->bind_param("i", $invoiceId);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows === 0) {
            json_response(false, "Invoice not found");
        }
        
        // Delete invoice
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