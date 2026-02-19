<?php
/**
 * Cancel an invoice
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
        $stmt = $conn->prepare("SELECT id, status FROM invoices WHERE id = ?");
        $stmt->bind_param("i", $invoiceId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            json_response(false, "Invoice not found");
        }
        
        $invoice = $result->fetch_assoc();
        
        if ($invoice['status'] === 'cancelled') {
            json_response(false, "Invoice is already cancelled");
        }
        
        if ($invoice['status'] === 'paid') {
            json_response(false, "Cannot cancel a paid invoice");
        }
        
        // Cancel invoice
        $stmt = $conn->prepare("
            UPDATE invoices 
            SET status = 'cancelled', updated_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->bind_param("i", $invoiceId);
        
        if ($stmt->execute()) {
            json_response(true, "Invoice cancelled successfully");
        } else {
            json_response(false, "Failed to cancel invoice: " . $stmt->error);
        }
        
    } catch (Exception $e) {
        json_response(false, "Error cancelling invoice: " . $e->getMessage());
    }
} else {
    json_response(false, "Invalid request method");
}
?>