<?php
$pageTitle = 'Edit Patient';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/helpers.php';

// Role-based access control
checkRole(['Admin', 'Receptionist']);

// Validate patient ID
$patientId = intval($_GET['id'] ?? 0);
if ($patientId <= 0) {
    die('<div class="alert alert-danger">Invalid patient ID.</div>');
}

// Fetch patient data
try {
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
    $stmt->execute([$patientId]);
    $patient = $stmt->fetch();
    
    if (!$patient) {
        die('<div class="alert alert-danger">Patient not found.</div>');
    }
} catch (PDOException $e) {
    error_log("Patient Fetch Error: " . $e->getMessage());
    die('<div class="alert alert-danger">Error fetching patient data.</div>');
}

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
                // Handle photo upload
                $profilePhoto = $patient['profile_photo']; // Keep existing photo by default
                $uploadDir = __DIR__ . '/../../assets/images/patients';
                
                if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
                    // Delete old photo if exists
                    if ($patient['profile_photo']) {
                        deletePatientPhoto($patient['profile_photo'], $uploadDir);
                    }
                    
                    // Upload new photo
                    $uploadResult = saveUploadedPhoto($_FILES['profile_photo'], $uploadDir);
                    if (!$uploadResult['success']) {
                        $error = $uploadResult['message'];
                    } else {
                        $profilePhoto = $uploadResult['filename'];
                    }
                }
                
                if (empty($error)) {
                    // Update patient record
                    $stmt = $pdo->prepare("UPDATE patients SET 
                        full_name = ?, gender = ?, date_of_birth = ?, phone = ?, email = ?, address = ?, 
                        blood_group = ?, emergency_contact_name = ?, emergency_contact_phone = ?, 
                        medical_notes = ?, profile_photo = ? 
                        WHERE id = ?");
                    
                    $result = $stmt->execute([
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
                        $patientId
                    ]);
                    
                    if ($result) {
                        // Log activity
                        logActivity('Patient Updated', "Patient updated: $fullName (Code: {$patient['patient_code']})");
                        
                        $success = 'Patient updated successfully!';
                        
                        // Refresh patient data
                        $stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
                        $stmt->execute([$patientId]);
                        $patient = $stmt->fetch();
                    } else {
                        $error = 'Failed to update patient. Please try again.';
                    }
                }
            } catch (Exception $e) {
                error_log("Patient Update Error: " . $e->getMessage());
                $error = 'An error occurred. Please try again.';
            }
        }
    }
}
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>Edit Patient</h2>
        <p class="text-muted mb-0">Update patient information - <?php echo htmlspecialchars($patient['patient_code']); ?></p>
    </div>
    <a href="<?php echo BASE_URL; ?>modules/patients/view.php?id=<?php echo $patient['id']; ?>" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to Profile
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
                           value="<?php echo htmlspecialchars($patient['full_name']); ?>">
                    <div class="invalid-feedback">Please provide full name.</div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="gender" class="form-label">Gender <span class="text-danger">*</span></label>
                    <select class="form-select" id="gender" name="gender" required>
                        <option value="">Select Gender</option>
                        <option value="Male" <?php echo $patient['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo $patient['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                        <option value="Other" <?php echo $patient['gender'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                    <div class="invalid-feedback">Please select gender.</div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="date_of_birth" class="form-label">Date of Birth</label>
                    <input type="date" class="form-control" id="date_of_birth" name="date_of_birth"
                           value="<?php echo htmlspecialchars($patient['date_of_birth'] ?? ''); ?>">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                    <input type="tel" class="form-control" id="phone" name="phone" required
                           value="<?php echo htmlspecialchars($patient['phone']); ?>">
                    <div class="invalid-feedback">Please provide phone number.</div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email"
                           value="<?php echo htmlspecialchars($patient['email'] ?? ''); ?>">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="blood_group" class="form-label">Blood Group</label>
                    <select class="form-select" id="blood_group" name="blood_group">
                        <option value="">Select Blood Group</option>
                        <option value="A+" <?php echo ($patient['blood_group'] ?? '') === 'A+' ? 'selected' : ''; ?>>A+</option>
                        <option value="A-" <?php echo ($patient['blood_group'] ?? '') === 'A-' ? 'selected' : ''; ?>>A-</option>
                        <option value="B+" <?php echo ($patient['blood_group'] ?? '') === 'B+' ? 'selected' : ''; ?>>B+</option>
                        <option value="B-" <?php echo ($patient['blood_group'] ?? '') === 'B-' ? 'selected' : ''; ?>>B-</option>
                        <option value="AB+" <?php echo ($patient['blood_group'] ?? '') === 'AB+' ? 'selected' : ''; ?>>AB+</option>
                        <option value="AB-" <?php echo ($patient['blood_group'] ?? '') === 'AB-' ? 'selected' : ''; ?>>AB-</option>
                        <option value="O+" <?php echo ($patient['blood_group'] ?? '') === 'O+' ? 'selected' : ''; ?>>O+</option>
                        <option value="O-" <?php echo ($patient['blood_group'] ?? '') === 'O-' ? 'selected' : ''; ?>>O-</option>
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
                <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($patient['address'] ?? ''); ?></textarea>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="emergency_contact_name" class="form-label">Emergency Contact Name</label>
                    <input type="text" class="form-control" id="emergency_contact_name" name="emergency_contact_name"
                           value="<?php echo htmlspecialchars($patient['emergency_contact_name'] ?? ''); ?>">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="emergency_contact_phone" class="form-label">Emergency Contact Phone</label>
                    <input type="tel" class="form-control" id="emergency_contact_phone" name="emergency_contact_phone"
                           value="<?php echo htmlspecialchars($patient['emergency_contact_phone'] ?? ''); ?>">
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
                          placeholder="Allergies, existing conditions, medications, etc."><?php echo htmlspecialchars($patient['medical_notes'] ?? ''); ?></textarea>
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
            <?php if ($patient['profile_photo']): ?>
                <div class="mb-3">
                    <label class="form-label">Current Photo</label>
                    <div>
                        <img src="<?php echo BASE_URL; ?>assets/images/patients/<?php echo htmlspecialchars($patient['profile_photo']); ?>" 
                             alt="Current Patient Photo" class="img-thumbnail" style="max-width: 200px;">
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="mb-3">
                <label for="profile_photo" class="form-label"><?php echo $patient['profile_photo'] ? 'Change Photo' : 'Upload Photo'; ?></label>
                <input type="file" class="form-control" id="profile_photo" name="profile_photo" accept="image/jpeg,image/png,image/jpg">
                <div class="form-text">Allowed formats: JPG, PNG. Maximum size: 2MB. Leave empty to keep current photo.</div>
            </div>
        </div>
    </div>
    
    <div class="d-flex justify-content-between">
        <a href="<?php echo BASE_URL; ?>modules/patients/view.php?id=<?php echo $patient['id']; ?>" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-save me-2"></i>Update Patient
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
