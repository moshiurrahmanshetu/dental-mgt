<?php
// Step 2: Database Configuration + SQL File Upload
require_once INSTALLER_ROOT . '/bootstrap.php';

// Session keys used in this file (Rule 1 documentation):
// $_SESSION['installer']['db_host']
// $_SESSION['installer']['db_port']
// $_SESSION['installer']['db_name']
// $_SESSION['installer']['db_username']
// $_SESSION['installer']['db_password']
// $_SESSION['installer']['sql_file_path']
// $_SESSION['installer']['connection_tested']

$error = '';
$success = '';

// Handle form submission (Rule 3: native HTML form submission)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Reset connection tested flag if any field changed
    $dbHost = trim($_POST['db_host'] ?? '');
    $dbPort = trim($_POST['db_port'] ?? '');
    $dbName = trim($_POST['db_name'] ?? '');
    $dbUsername = trim($_POST['db_username'] ?? '');
    $dbPassword = $_POST['db_password'] ?? '';
    
    // Clear connection tested flag if values changed
    $testedDbHost = getInstallerSession('db_host', '');
    $testedDbPort = getInstallerSession('db_port', '');
    $testedDbName = getInstallerSession('db_name', '');
    $testedDbUsername = getInstallerSession('db_username', '');
    $testedDbPassword = getInstallerSession('db_password', '');
    
    if ($dbHost !== $testedDbHost || $dbPort !== $testedDbPort || 
        $dbName !== $testedDbName || $dbUsername !== $testedDbUsername || 
        $dbPassword !== $testedDbPassword) {
        setInstallerSession('connection_tested', false);
    }
    
    // Validate required fields
    if (empty($dbHost) || empty($dbPort) || empty($dbName) || empty($dbUsername)) {
        $error = 'All database fields are required.';
    } elseif (!isset($_FILES['sql_file']) || $_FILES['sql_file']['error'] !== UPLOAD_ERR_OK) {
        $error = 'SQL file is required.';
    } else {
        // Validate SQL file (Rule 4: server-side validation)
        $sqlFile = $_FILES['sql_file'];
        $allowedExtensions = ['sql'];
        $fileExtension = strtolower(pathinfo($sqlFile['name'], PATHINFO_EXTENSION));
        
        if (!in_array($fileExtension, $allowedExtensions)) {
            $error = 'Invalid file type. Only .sql files are allowed.';
        } elseif ($sqlFile['size'] > (int)str_replace('M', '', getUploadMaxFileSize()) * 1024 * 1024) {
            $error = 'File size exceeds maximum upload limit of ' . getUploadMaxFileSize();
        } else {
            // Check connection was tested (Rule: require successful test before proceeding)
            if (!getInstallerSession('connection_tested', false)) {
                $error = 'Please test the database connection before proceeding.';
            } else {
                // Verify temp folder exists and is writable (Rule 4)
                if (!is_dir(TEMP_PATH)) {
                    mkdir(TEMP_PATH, 0755, true);
                }
                
                if (!is_writable(TEMP_PATH)) {
                    $error = 'Temp folder is not writable. Please check permissions.';
                } else {
                    // Generate unique filename for uploaded SQL file
                    $tempFileName = 'import_' . time() . '_' . bin2hex(random_bytes(8)) . '.sql';
                    $tempFilePath = TEMP_PATH . '/' . $tempFileName;
                    
                    // Move uploaded file (Rule 4: check return value)
                    if (move_uploaded_file($sqlFile['tmp_name'], $tempFilePath)) {
                        // Save to session IMMEDIATELY (Rule 4)
                        setInstallerSession('sql_file_path', $tempFilePath);
                        setInstallerSession('db_host', $dbHost);
                        setInstallerSession('db_port', $dbPort);
                        setInstallerSession('db_name', $dbName);
                        setInstallerSession('db_username', $dbUsername);
                        setInstallerSession('db_password', $dbPassword);
                        setInstallerSession('step2', true);
                        
                        // Redirect to step 3
                        header('Location: index.php?step=3');
                        exit();
                    } else {
                        $error = 'Failed to upload SQL file. Please check temp folder permissions.';
                    }
                }
            }
        }
    }
}

// Pre-fill form with saved values
$dbHost = getInstallerSession('db_host', 'localhost');
$dbPort = getInstallerSession('db_port', '3306');
$dbName = getInstallerSession('db_name', '');
$dbUsername = getInstallerSession('db_username', '');
$dbPassword = getInstallerSession('db_password', '');
$connectionTested = getInstallerSession('connection_tested', false);
?>

<h3>Database Configuration</h3>
<p>Enter your database connection details and upload the SQL file containing the database structure and seed data.</p>

<?php if ($error): ?>
    <div class="error-message">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<!-- Rule 3: enctype="multipart/form-data" is required for file upload -->
<form method="POST" action="" enctype="multipart/form-data" onsubmit="return validateStep2();">
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="db_host" class="form-label">Database Host</label>
            <input type="text" class="form-control" id="db_host" name="db_host" 
                   value="<?php echo htmlspecialchars($dbHost); ?>" required>
        </div>
        
        <div class="col-md-6 mb-3">
            <label for="db_port" class="form-label">Database Port</label>
            <input type="text" class="form-control" id="db_port" name="db_port" 
                   value="<?php echo htmlspecialchars($dbPort); ?>" required>
        </div>
    </div>
    
    <div class="mb-3">
        <label for="db_name" class="form-label">Database Name</label>
        <input type="text" class="form-control" id="db_name" name="db_name" 
               value="<?php echo htmlspecialchars($dbName); ?>" required>
    </div>
    
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="db_username" class="form-label">Database Username</label>
            <input type="text" class="form-control" id="db_username" name="db_username" 
                   value="<?php echo htmlspecialchars($dbUsername); ?>" required>
        </div>
        
        <div class="col-md-6 mb-3">
            <label for="db_password" class="form-label">Database Password</label>
            <div class="input-group">
                <input type="password" class="form-control" id="db_password" name="db_password" 
                       value="<?php echo htmlspecialchars($dbPassword); ?>">
                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('db_password')">
                    <i class="bi bi-eye" id="db_password_icon"></i>
                </button>
            </div>
        </div>
    </div>
    
    <div class="mb-3">
        <label for="sql_file" class="form-label">Database SQL File</label>
        <input type="file" class="form-control" id="sql_file" name="sql_file" 
               accept=".sql" required>
        <div class="form-text">
            Maximum file size: <?php echo getUploadMaxFileSize(); ?>
        </div>
    </div>
    
    <!-- Rule 3: Test Connection is separate button, not type="submit" -->
    <div class="mb-3">
        <button type="button" class="btn btn-outline-primary" onclick="testConnection()">
            <i class="bi bi-plug"></i> Test Connection
        </button>
        <span id="connection-status" class="ms-2"></span>
    </div>
    
    <div class="navigation-buttons">
        <button type="button" class="btn btn-secondary" onclick="window.location.href='index.php?step=1'">
            <i class="bi bi-arrow-left"></i> Previous
        </button>
        <button type="submit" class="btn btn-primary" id="next-btn" disabled>
            Next <i class="bi bi-arrow-right"></i>
        </button>
    </div>
</form>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = document.getElementById(fieldId + '_icon');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}

function testConnection() {
    const dbHost = document.getElementById('db_host').value;
    const dbPort = document.getElementById('db_port').value;
    const dbName = document.getElementById('db_name').value;
    const dbUsername = document.getElementById('db_username').value;
    const dbPassword = document.getElementById('db_password').value;
    const statusSpan = document.getElementById('connection-status');
    
    if (!dbHost || !dbPort || !dbUsername) {
        statusSpan.innerHTML = '<span class="text-danger">Please fill in all database fields first.</span>';
        return;
    }
    
    statusSpan.innerHTML = '<span class="text-muted"><i class="bi bi-hourglass-split"></i> Testing...</span>';
    
    // Rule 3: Separate AJAX call for test connection
    fetch('test-connection.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'db_host=' + encodeURIComponent(dbHost) + 
              '&db_port=' + encodeURIComponent(dbPort) + 
              '&db_name=' + encodeURIComponent(dbName) +
              '&db_username=' + encodeURIComponent(dbUsername) + 
              '&db_password=' + encodeURIComponent(dbPassword)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            statusSpan.innerHTML = '<span class="text-success"><i class="bi bi-check-circle-fill"></i> Connection successful!</span>';
            document.getElementById('next-btn').disabled = false;
        } else {
            statusSpan.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle-fill"></i> ' + data.message + '</span>';
            document.getElementById('next-btn').disabled = true;
        }
    })
    .catch(error => {
        statusSpan.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle-fill"></i> Connection failed: ' + error.message + '</span>';
        document.getElementById('next-btn').disabled = true;
    })
    .finally(() => {
        // Ensure button state is reset regardless of success/failure
        // (Prevents button from being stuck in any state)
    });
}

function validateStep2() {
    const dbHost = document.getElementById('db_host').value;
    const dbPort = document.getElementById('db_port').value;
    const dbName = document.getElementById('db_name').value;
    const dbUsername = document.getElementById('db_username').value;
    const sqlFile = document.getElementById('sql_file').value;
    
    if (!dbHost || !dbPort || !dbName || !dbUsername) {
        alert('Please fill in all database fields.');
        return false;
    }
    
    if (!sqlFile) {
        alert('Please select a SQL file to upload.');
        return false;
    }
    
    return true;
}

// Enable/disable next button based on connection test status
<?php if ($connectionTested): ?>
document.getElementById('next-btn').disabled = false;
<?php endif; ?>
</script>
