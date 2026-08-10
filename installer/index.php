<?php
require_once 'bootstrap.php';

// Check if already installed (Rule 7)
checkAlreadyInstalled();

// Get current step from URL parameter
$currentStep = isset($_GET['step']) ? intval($_GET['step']) : 1;

// Validate step is between 1-4
if ($currentStep < 1 || $currentStep > 4) {
    $currentStep = 1;
}

// Ensure steps are sequential - user can't skip ahead
if ($currentStep > 1) {
    $prevStepData = getInstallerSession('step' . ($currentStep - 1), false);
    if (!$prevStepData) {
        $currentStep = 1;
    }
}

$pageTitle = 'Installation Wizard - Step ' . $currentStep;

// Process step logic first (allows redirects to work)
// We use output buffering to capture any HTML output from step processing
// If the step redirects, the script will exit and buffering will be discarded
ob_start();
$stepFile = INSTALLER_ROOT . '/step' . $currentStep . '.php';
if (file_exists($stepFile)) {
    include $stepFile;
} else {
    die('<div class="error-message">Step file not found: ' . htmlspecialchars($stepFile) . '</div>');
}
$stepContent = ob_get_clean();

// If we reach here, no redirect happened, so output the HTML layout
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Dental Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .installer-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 800px;
            width: 100%;
        }
        .installer-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 12px 12px 0 0;
            text-align: center;
        }
        .installer-header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .installer-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }
        .progress-stepper {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 30px;
            background: #f8f9fa;
            border-radius: 0 0 12px 12px;
        }
        .step-indicator {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 15px;
            font-weight: 600;
            background: #e9ecef;
            color: #6c757d;
            border: 2px solid #e9ecef;
        }
        .step-indicator.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        .step-indicator.completed {
            background: #28a745;
            color: white;
            border-color: #28a745;
        }
        .step-label {
            position: absolute;
            bottom: -25px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 12px;
            color: #6c757d;
            white-space: nowrap;
        }
        .step-container {
            position: relative;
            display: inline-block;
        }
        .installer-body {
            padding: 30px;
        }
        .requirement-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        .requirement-item .icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 18px;
        }
        .requirement-item.success .icon {
            background: #d4edda;
            color: #155724;
        }
        .requirement-item.error .icon {
            background: #f8d7da;
            color: #721c24;
        }
        .requirement-item.warning .icon {
            background: #fff3cd;
            color: #856404;
        }
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        .success-message {
            background: #d4edda;
            color: 155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        .navigation-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }
    </style>
</head>
<body>
    <div class="installer-card">
        <div class="installer-header">
            <h1><i class="bi bi-hospital"></i> Dental Management System</h1>
            <p>Installation Wizard - Step <?php echo $currentStep; ?> of 4</p>
        </div>
        
        <div class="progress-stepper">
            <?php for ($i = 1; $i <= 4; $i++): ?>
                <div class="step-container">
                    <div class="step-indicator <?php 
                        echo $i < $currentStep ? 'completed' : ($i == $currentStep ? 'active' : '');
                    ?>">
                        <?php if ($i < $currentStep): ?>
                            <i class="bi bi-check"></i>
                        <?php else: ?>
                            <?php echo $i; ?>
                        <?php endif; ?>
                    </div>
                    <span class="step-label">
                        <?php 
                        $labels = ['Requirements', 'Database', 'Admin', 'Install'];
                        echo $labels[$i - 1];
                        ?>
                    </span>
                </div>
            <?php endfor; ?>
        </div>
        
        <div class="installer-body">
            <?php echo $stepContent; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
