<?php
// Application Constants
define('BASE_URL', 'http://localhost:8000/');
define('SITE_NAME', 'Dental Management System');
define('SITE_EMAIL', 'admin@dentalcare.com');

// Database Constants
define('DB_HOST', 'localhost');
define('DB_NAME', 'dental_management_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Session Configuration
define('SESSION_LIFETIME', 3600); // 1 hour in seconds

// Role Constants
define('ROLE_ADMIN', 'Admin');
define('ROLE_DOCTOR', 'Doctor');
define('ROLE_RECEPTIONIST', 'Receptionist');
define('ROLE_PATIENT', 'Patient');

// User Status
define('STATUS_ACTIVE', 'active');
define('STATUS_INACTIVE', 'inactive');

// Pagination
define('RECORDS_PER_PAGE', 10);

// Timezone
date_default_timezone_set('UTC');
?>
