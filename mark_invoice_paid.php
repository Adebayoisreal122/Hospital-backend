<?php
/**
 * Mark an invoice as paid
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
        $paymentMethod = $input['payment_method'] ?? '';
        
        // Validation
        if (!$invoiceId) {
            json_response(false, "Invoice ID is required");
        }
        
        if (empty($paymentMethod)) {
            json_response(false, "Payment method is required");
        }
        
        // Check if invoice exists and is pending
        $stmt = $conn->prepare("SELECT id, status FROM invoices WHERE id = ?");
        $stmt->bind_param("i", $invoiceId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            json_response(false, "Invoice not found");
        }
        
        $invoice = $result->fetch_assoc();
        
        if ($invoice['status'] === 'paid') {
            json_response(false, "Invoice is already marked as paid");
        }
        
        if ($invoice['status'] === 'cancelled') {
            json_response(false, "Cannot mark a cancelled invoice as paid");
        }
        
        $stmt = $conn->prepare("
            UPDATE invoices 
            SET status = 'paid', 
                payment_method = ?, 
                payment_date = NOW(), 
                updated_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->bind_param("si", $paymentMethod, $invoiceId);
        
        if ($stmt->execute()) {
            json_response(true, "Invoice marked as paid successfully");
        } else {
            json_response(false, "Failed to update invoice: " . $stmt->error);
        }
        
    } catch (Exception $e) {
        json_response(false, "Error updating invoice: " . $e->getMessage());
    }
} else {
    json_response(false, "Invalid request method");
}
?>