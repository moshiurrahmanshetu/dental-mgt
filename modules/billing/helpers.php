<?php
// Billing Management Helper Functions

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_functions.php';

/**
 * Generate next invoice number in format INV-000001
 * Uses transaction to prevent race conditions
 * 
 * @param PDO $pdo Database connection
 * @return string Next invoice number
 */
function generateInvoiceNumber($pdo) {
    try {
        // Only start transaction if one is not already active
        $transactionStarted = false;
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $transactionStarted = true;
        }
        
        // Get the maximum numeric part from existing invoice numbers
        $stmt = $pdo->prepare("SELECT MAX(CAST(SUBSTRING(invoice_number, 5) AS UNSIGNED)) as max_code FROM invoices");
        $stmt->execute();
        $result = $stmt->fetch();
        
        $maxCode = $result['max_code'] ?? 0;
        $nextCode = $maxCode + 1;
        
        // Pad with leading zeros to 6 digits
        $paddedCode = str_pad($nextCode, 6, '0', STR_PAD_LEFT);
        $invoiceNumber = 'INV-' . $paddedCode;
        
        // Only commit if we started the transaction
        if ($transactionStarted) {
            $pdo->commit();
        }
        
        return $invoiceNumber;
    } catch (PDOException $e) {
        if ($transactionStarted && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Invoice Number Generation Error: " . $e->getMessage());
        throw new Exception("Failed to generate invoice number");
    }
}

/**
 * Generate next payment code in format PAY-000001
 * Uses transaction to prevent race conditions
 * 
 * @param PDO $pdo Database connection
 * @return string Next payment code
 */
function generatePaymentCode($pdo) {
    try {
        // Only start transaction if one is not already active
        $transactionStarted = false;
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $transactionStarted = true;
        }
        
        // Get the maximum numeric part from existing payment codes
        $stmt = $pdo->prepare("SELECT MAX(CAST(SUBSTRING(payment_code, 5) AS UNSIGNED)) as max_code FROM payments");
        $stmt->execute();
        $result = $stmt->fetch();
        
        $maxCode = $result['max_code'] ?? 0;
        $nextCode = $maxCode + 1;
        
        // Pad with leading zeros to 6 digits
        $paddedCode = str_pad($nextCode, 6, '0', STR_PAD_LEFT);
        $paymentCode = 'PAY-' . $paddedCode;
        
        // Only commit if we started the transaction
        if ($transactionStarted) {
            $pdo->commit();
        }
        
        return $paymentCode;
    } catch (PDOException $e) {
        if ($transactionStarted && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Payment Code Generation Error: " . $e->getMessage());
        throw new Exception("Failed to generate payment code");
    }
}

/**
 * Recalculate invoice totals and payment status
 * This is the single source of truth for keeping paid_amount, due_amount, and payment_status in sync
 * 
 * @param PDO $pdo Database connection
 * @param int $invoiceId Invoice ID
 * @return bool Success status
 */
function recalculateInvoiceTotals($pdo, $invoiceId) {
    try {
        // Get invoice details (total_amount is already set, we need to update paid_amount)
        $stmt = $pdo->prepare("SELECT total_amount FROM invoices WHERE id = ?");
        $stmt->execute([$invoiceId]);
        $invoice = $stmt->fetch();
        
        if (!$invoice) {
            return false;
        }
        
        // Sum all payments for this invoice
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total_paid FROM payments WHERE invoice_id = ?");
        $stmt->execute([$invoiceId]);
        $result = $stmt->fetch();
        $paidAmount = $result['total_paid'];
        
        // Calculate due amount
        $dueAmount = $invoice['total_amount'] - $paidAmount;
        
        // Determine payment status
        if ($paidAmount <= 0) {
            $paymentStatus = 'Unpaid';
        } elseif ($paidAmount < $invoice['total_amount']) {
            $paymentStatus = 'Partially Paid';
        } else {
            $paymentStatus = 'Paid';
        }
        
        // Update invoice with recalculated values
        $stmt = $pdo->prepare("UPDATE invoices 
            SET paid_amount = ?, 
            due_amount = ?, 
            payment_status = ?, 
            updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?");
        
        $stmt->execute([$paidAmount, $dueAmount, $paymentStatus, $invoiceId]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Invoice Totals Recalculation Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get payment status badge class
 * 
 * @param string $status Payment status
 * @return string Bootstrap badge class
 */
function getPaymentStatusBadgeClass($status) {
    switch ($status) {
        case 'Paid':
            return 'bg-success';
        case 'Partially Paid':
            return 'bg-warning';
        case 'Unpaid':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
}
?>
