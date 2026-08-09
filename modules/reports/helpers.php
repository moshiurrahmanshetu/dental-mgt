<?php
// Reports Helper Functions

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_functions.php';

/**
 * Export data to CSV
 * 
 * @param array $data Data to export (array of arrays)
 * @param string $filename CSV filename
 * @param array $headers Column headers
 */
function exportToCSV($data, $filename, $headers) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8 Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Write headers
    fputcsv($output, $headers);
    
    // Write data
    foreach ($data as $row) {
        // Sanitize data
        $sanitizedRow = array_map(function($value) {
            return strip_tags($value);
        }, $row);
        fputcsv($output, $sanitizedRow);
    }
    
    fclose($output);
    exit();
}

/**
 * Format date for display
 * 
 * @param string $date Date string
 * @return string Formatted date
 */
function formatDate($date) {
    if (empty($date)) return '--';
    return date('M d, Y', strtotime($date));
}

/**
 * Format currency for display
 * 
 * @param float $amount Amount
 * @return string Formatted currency
 */
function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}

/**
 * Get report date range from GET parameters
 * 
 * @return array ['from_date', 'to_date']
 */
function getReportDateRange() {
    $fromDate = trim($_GET['from_date'] ?? '');
    $toDate = trim($_GET['to_date'] ?? '');
    
    // Default to current month if not specified
    if (empty($fromDate) && empty($toDate)) {
        $fromDate = date('Y-m-01');
        $toDate = date('Y-m-t');
    }
    
    return [
        'from_date' => $fromDate,
        'to_date' => $toDate
    ];
}

/**
 * Build date range SQL condition
 * 
 * @param string $dateColumn Date column name
 * @param array $dateRange Date range from getReportDateRange()
 * @return array ['condition', 'params']
 */
function buildDateRangeCondition($dateColumn, $dateRange) {
    $conditions = [];
    $params = [];
    
    if (!empty($dateRange['from_date'])) {
        $conditions[] = "$dateColumn >= ?";
        $params[] = $dateRange['from_date'];
    }
    
    if (!empty($dateRange['to_date'])) {
        $conditions[] = "$dateColumn <= ?";
        $params[] = $dateRange['to_date'];
    }
    
    $condition = '';
    if (!empty($conditions)) {
        $condition = 'AND ' . implode(' AND ', $conditions);
    }
    
    return ['condition' => $condition, 'params' => $params];
}
?>
