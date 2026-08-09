<?php
$pageTitle = 'Add New Patient';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/helpers.php';

// Role-based access control
checkRole(['Admin', 'Receptionist']);

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        // Collect and sanitize form data
        $fullName = trim($_POST['full_name'] ?? '');
        $gender = $_POST['gender'] ?? '';
        $dateOfBirth = $_POST['date_of_birth'] ?? '';
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $bloodGroup = trim($_POST['blood_group'] ?? '');
        $emergencyContactName = trim($_POST['emergency_contact_name'] ?? '');
        $emergencyContactPhone = trim($_POST['emergency_contact_phone'] ?? '');
        $medicalNotes = trim($_POST['medical_notes'] ?? '');
        
        // Server-side validation
        if (empty($fullName)) {
            $error = 'Full name is required.';
        } elseif (empty($phone)) {
            $error = 'Phone number is required.';
        } elseif (empty($gender)) {
            $error = 'Gender is required.';
        } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email format.';
        } else {
            try {
                // Generate patient code
                $patientCode = generatePatientCode($pdo);
                
                // Handle photo upload
                $profilePhoto = null;
                $uploadDir = __DIR__ . '/../../assets/images/patients';
                
                if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $uploadResult = saveUploadedPhoto($_FILES['profile_photo'], $uploadDir);
                    if (!$uploadResult['success']) {
                        $error = $uploadResult['message'];
                    } else {
                        $profilePhoto = $uploadResult['filename'];
                    }
                }
                
                if (empty($error)) {
                    // Insert patient record
                    $stmt = $pdo->prepare("INSERT INTO patients 
                        (patient_code, user_id, full_name, gender, date_of_birth, phone, email, address, 
                         blood_group, emergency_contact_name, emergency_contact_phone, medical_notes, 
                         profile_photo, registered_by, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    
                    $result = $stmt->execute([
                        $patientCode,
                        null, // user_id (NULL for walk-in patients)
                        $fullName,
                        $gender,
                        !empty($dateOfBirth) ? $dateOfBirth : null,
                        $phone,
                        !empty($email) ? $email : null,
                        !empty($address) ? $address : null,
                        !empty($bloodGroup) ? $bloodGroup : null,
                        !empty($emergencyContactName) ? $emergencyContactName : null,
                        !empty($emergencyContactPhone) ? $emergencyContactPhone : null,
                        !empty($medicalNotes) ? $medicalNotes : null,
                        $profilePhoto,
                        $_SESSION['user_id'], // registered_by
                        'active'
                    ]);
                    
                    if ($result) {
                        // Log activity
                        logActivity('Patient Added', "New patient added: $fullName (Code: $patientCode)");
                        
                        $success = 'Patient added successfully!';
                        
                        // Redirect to list page after short delay
                        header('refresh:2;url=' . BASE_URL . 'modules/patients/list.php');
                    } else {
                        $error = 'Failed to add patient. Please try again.';
                        
                        // Clean up uploaded photo if insert failed
                        if ($profilePhoto) {
                            deletePatientPhoto($profilePhoto, $uploadDir);
                        }
                    }
                }
            } catch (Exception $e) {
                error_log("Patient Add Error: " . $e->getMessage());
                $error = 'An error occurred. Please try again.';
                
                // Clean up uploaded photo if error occurred
                if (isset($profilePhoto) && $profilePhoto) {
                    deletePatientPhoto($profilePhoto, $uploadDir);
                }
            }
        }
    }
}
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>Add New Patient</h2>
        <p class="text-muted mb-0">Register a new patient in the system</p>
    </div>
    <a href="<?php echo BASE_URL; ?>modules/patients/list.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to List
    </a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($success); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form method="POST" action="" enctype="multipart/form-data" class="needs-validation" novalidate>
    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
    
    <!-- Personal Information -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Personal Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="full_name" name="full_name" required
                           value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                    <div class="invalid-feedback">Please provide full name.</div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="gender" class="form-label">Gender <span class="text-danger">*</span></label>
                    <select class="form-select" id="gender" name="gender" required>
                        <option value="">Select Gender</option>
                        <option value="Male" <?php echo (($_POST['gender'] ?? '') === 'Male' ? 'selected' : ''); ?>>Male</option>
                        <option value="Female" <?php echo (($_POST['gender'] ?? '') === 'Female' ? 'selected' : ''); ?>>Female</option>
                        <option value="Other" <?php echo (($_POST['gender'] ?? '') === 'Other' ? 'selected' : ''); ?>>Other</option>
                    </select>
                    <div class="invalid-feedback">Please select gender.</div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="date_of_birth" class="form-label">Date of Birth</label>
                    <input type="date" class="form-control" id="date_of_birth" name="date_of_birth"
                           value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                    <input type="tel" class="form-control" id="phone" name="phone" required
                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                    <div class="invalid-feedback">Please provide phone number.</div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="blood_group" class="form-label">Blood Group</label>
                    <select class="form-select" id="blood_group" name="blood_group">
                        <option value="">Select Blood Group</option>
                        <option value="A+" <?php echo (($_POST['blood_group'] ?? '') === 'A+' ? 'selected' : ''); ?>>A+</option>
                        <option value="A-" <?php echo (($_POST['blood_group'] ?? '') === 'A-' ? 'selected' : ''); ?>>A-</option>
                        <option value="B+" <?php echo (($_POST['blood_group'] ?? '') === 'B+' ? 'selected' : ''); ?>>B+</option>
                        <option value="B-" <?php echo (($_POST['blood_group'] ?? '') === 'B-' ? 'selected' : ''); ?>>B-</option>
                        <option value="AB+" <?php echo (($_POST['blood_group'] ?? '') === 'AB+' ? 'selected' : ''); ?>>AB+</option>
                        <option value="AB-" <?php echo (($_POST['blood_group'] ?? '') === 'AB-' ? 'selected' : ''); ?>>AB-</option>
                        <option value="O+" <?php echo (($_POST['blood_group'] ?? '') === 'O+' ? 'selected' : ''); ?>>O+</option>
                        <option value="O-" <?php echo (($_POST['blood_group'] ?? '') === 'O-' ? 'selected' : ''); ?>>O-</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Contact Information -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Contact Information</h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="address" class="form-label">Address</label>
                <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="emergency_contact_name" class="form-label">Emergency Contact Name</label>
                    <input type="text" class="form-control" id="emergency_contact_name" name="emergency_contact_name"
                           value="<?php echo htmlspecialchars($_POST['emergency_contact_name'] ?? ''); ?>">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="emergency_contact_phone" class="form-label">Emergency Contact Phone</label>
                    <input type="tel" class="form-control" id="emergency_contact_phone" name="emergency_contact_phone"
                           value="<?php echo htmlspecialchars($_POST['emergency_contact_phone'] ?? ''); ?>">
                </div>
            </div>
        </div>
    </div>
    
    <!-- Medical Information -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Medical Information</h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="medical_notes" class="form-label">Medical Notes</label>
                <textarea class="form-control" id="medical_notes" name="medical_notes" rows="4"
                          placeholder="Allergies, existing conditions, medications, etc."><?php echo htmlspecialchars($_POST['medical_notes'] ?? ''); ?></textarea>
                <div class="form-text">Include any relevant medical information such as allergies, chronic conditions, or current medications.</div>
            </div>
        </div>
    </div>
    
    <!-- Profile Photo -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Profile Photo</h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="profile_photo" class="form-label">Upload Photo</label>
                <input type="file" class="form-control" id="profile_photo" name="profile_photo" accept="image/jpeg,image/png,image/jpg">
                <div class="form-text">Allowed formats: JPG, PNG. Maximum size: 2MB.</div>
            </div>
        </div>
    </div>
    
    <div class="d-flex justify-content-between">
        <a href="<?php echo BASE_URL; ?>modules/patients/list.php" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-save me-2"></i>Save Patient
        </button>
    </div>
</form>

<script>
// Form validation
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms)
        .forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
})()
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
