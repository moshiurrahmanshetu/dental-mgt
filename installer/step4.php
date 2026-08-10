<?php
// Step 4: Install Summary
require_once INSTALLER_ROOT . '/bootstrap.php';

// Get all session data for summary
$dbHost = getInstallerSession('db_host', '');
$dbPort = getInstallerSession('db_port', '');
$dbName = getInstallerSession('db_name', '');
$dbUsername = getInstallerSession('db_username', '');
$appName = getInstallerSession('app_name', '');
$adminFullName = getInstallerSession('admin_full_name', '');
$adminEmail = getInstallerSession('admin_email', '');
$sqlFilePath = getInstallerSession('sql_file_path', '');
?>

<h3>Installation Summary</h3>
<p>Please review the configuration below before proceeding with the installation.</p>

<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="bi bi-database"></i> Database Configuration</h5>
    </div>
    <div class="card-body">
        <table class="table table-borderless">
            <tr>
                <td><strong>Host:</strong></td>
                <td><?php echo htmlspecialchars($dbHost); ?></td>
            </tr>
            <tr>
                <td><strong>Port:</strong></td>
                <td><?php echo htmlspecialchars($dbPort); ?></td>
            </tr>
            <tr>
                <td><strong>Database Name:</strong></td>
                <td><?php echo htmlspecialchars($dbName); ?></td>
            </tr>
            <tr>
                <td><strong>Username:</strong></td>
                <td><?php echo htmlspecialchars($dbUsername); ?></td>
            </tr>
            <tr>
                <td><strong>SQL File:</strong></td>
                <td><?php echo $sqlFilePath ? 'Uploaded and ready' : 'Not uploaded'; ?></td>
            </tr>
        </table>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0"><i class="bi bi-gear"></i> Application Settings</h5>
    </div>
    <div class="card-body">
        <table class="table table-borderless">
            <tr>
                <td><strong>Application Name:</strong></td>
                <td><?php echo htmlspecialchars($appName); ?></td>
            </tr>
        </table>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0"><i class="bi bi-person"></i> Administrator Account</h5>
    </div>
    <div class="card-body">
        <table class="table table-borderless">
            <tr>
                <td><strong>Full Name:</strong></td>
                <td><?php echo htmlspecialchars($adminFullName); ?></td>
            </tr>
            <tr>
                <td><strong>Email:</strong></td>
                <td><?php echo htmlspecialchars($adminEmail); ?></td>
            </tr>
            <tr>
                <td><strong>Password:</strong></td>
                <td>•••••••• (set)</td>
            </tr>
        </table>
    </div>
</div>

<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <strong>Important:</strong> This will create the database and tables. Make sure you have backed up any existing data before proceeding.
</div>

<div id="install-progress" style="display: none;">
    <div class="card">
        <div class="card-body">
            <h5 class="mb-3">Installation Progress</h5>
            <div class="progress mb-3">
                <div class="progress-bar progress-bar-striped progress-bar-animated" 
                     id="progress-bar" role="progressbar" style="width: 0%"></div>
            </div>
            <div id="progress-text" class="text-muted">Initializing...</div>
        </div>
    </div>
</div>

<div id="install-result" style="display: none;"></div>

<div class="navigation-buttons" id="install-buttons">
    <button type="button" class="btn btn-secondary" onclick="window.location.href='index.php?step=3'">
        <i class="bi bi-arrow-left"></i> Previous
    </button>
    <button type="button" class="btn btn-primary" onclick="startInstall()">
        <i class="bi bi-play-fill"></i> Install Now
    </button>
</div>

<script>
function startInstall() {
    if (!confirm('Are you sure you want to proceed with the installation? This will create the database and tables.')) {
        return;
    }
    
    // Hide install buttons, show progress
    document.getElementById('install-buttons').style.display = 'none';
    document.getElementById('install-progress').style.display = 'block';
    
    // Start installation via AJAX
    const progressBar = document.getElementById('progress-bar');
    const progressText = document.getElementById('progress-text');
    
    progressText.textContent = 'Connecting to database...';
    progressBar.style.width = '10%';
    
    fetch('process.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            progressBar.style.width = '100%';
            progressBar.classList.remove('progress-bar-striped', 'progress-bar-animated');
            progressBar.classList.add('bg-success');
            progressText.textContent = 'Installation completed successfully!';
            
            // Show success message
            document.getElementById('install-progress').style.display = 'none';
            document.getElementById('install-result').style.display = 'block';
            document.getElementById('install-result').innerHTML = `
                <div class="success-message">
                    <h4><i class="bi bi-check-circle-fill"></i> Installation Successful!</h4>
                    <p>Your Dental Management System has been installed successfully.</p>
                    <p><strong>Admin Email:</strong> ${data.admin_email}</p>
                    <p class="mb-0"><a href="../modules/auth/login.php" class="btn btn-success btn-lg">
                        <i class="bi bi-box-arrow-in-right"></i> Go to Login
                    </a></p>
                </div>
                <div class="alert alert-danger mt-3">
                    <i class="bi bi-shield-lock"></i>
                    <strong>Security Warning:</strong> Please delete the /installer folder from your server to prevent re-installation.
                </div>
            `;
        } else {
            progressBar.classList.remove('progress-bar-striped', 'progress-bar-animated');
            progressBar.classList.add('bg-danger');
            progressText.textContent = 'Installation failed!';
            
            // Show error message
            document.getElementById('install-progress').style.display = 'none';
            document.getElementById('install-result').style.display = 'block';
            document.getElementById('install-result').innerHTML = `
                <div class="error-message">
                    <h4><i class="bi bi-x-circle-fill"></i> Installation Failed</h4>
                    <p class="mb-0">${data.message}</p>
                </div>
                <div class="mt-3">
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='index.php?step=2'">
                        <i class="bi bi-arrow-left"></i> Go Back to Step 2
                    </button>
                </div>
            `;
        }
    })
    .catch(error => {
        progressBar.classList.remove('progress-bar-striped', 'progress-bar-animated');
        progressBar.classList.add('bg-danger');
        progressText.textContent = 'Installation failed!';
        
        document.getElementById('install-progress').style.display = 'none';
        document.getElementById('install-result').style.display = 'block';
        document.getElementById('install-result').innerHTML = `
            <div class="error-message">
                <h4><i class="bi bi-x-circle-fill"></i> Installation Failed</h4>
                <p class="mb-0">An error occurred: ${error.message}</p>
            </div>
            <div class="mt-3">
                <button type="button" class="btn btn-secondary" onclick="window.location.href='index.php?step=2'">
                    <i class="bi bi-arrow-left"></i> Go Back
                </button>
            </div>
        `;
    });
}
</script>
