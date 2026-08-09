<?php
// Get current role (session already started in auth_check.php)
$currentRole = $_SESSION['role_name'] ?? '';
$currentPage = basename($_SERVER['PHP_SELF']);

// Define menu items based on role
$menuItems = [
    'Admin' => [
        ['icon' => 'bi-speedometer2', 'label' => 'Dashboard', 'link' => 'admin.php'],
        ['icon' => 'bi-people', 'label' => 'Patients', 'link' => '../modules/patients/list.php'],
        ['icon' => 'bi-calendar-check', 'label' => 'Appointments', 'link' => '#'],
        ['icon' => 'bi-currency-dollar', 'label' => 'Billing', 'link' => '#'],
        ['icon' => 'bi-people-fill', 'label' => 'Doctors', 'link' => '#'],
        ['icon' => 'bi-person-badge', 'label' => 'Receptionists', 'link' => '#'],
        ['icon' => 'bi-gear', 'label' => 'Settings', 'link' => '#'],
    ],
    'Doctor' => [
        ['icon' => 'bi-speedometer2', 'label' => 'Dashboard', 'link' => 'doctor.php'],
        ['icon' => 'bi-people', 'label' => 'My Patients', 'link' => '../modules/patients/list.php'],
        ['icon' => 'bi-calendar-check', 'label' => 'Appointments', 'link' => '#'],
        ['icon' => 'bi-journal-medical', 'label' => 'Medical Records', 'link' => '#'],
    ],
    'Receptionist' => [
        ['icon' => 'bi-speedometer2', 'label' => 'Dashboard', 'link' => 'receptionist.php'],
        ['icon' => 'bi-people', 'label' => 'Patients', 'link' => '../modules/patients/list.php'],
        ['icon' => 'bi-calendar-check', 'label' => 'Appointments', 'link' => '#'],
        ['icon' => 'bi-currency-dollar', 'label' => 'Billing', 'link' => '#'],
    ],
    'Patient' => [
        ['icon' => 'bi-speedometer2', 'label' => 'Dashboard', 'link' => 'patient.php'],
        ['icon' => 'bi-calendar-check', 'label' => 'My Appointments', 'link' => '#'],
        ['icon' => 'bi-journal-medical', 'label' => 'Medical History', 'link' => '#'],
        ['icon' => 'bi-currency-dollar', 'label' => 'My Bills', 'link' => '#'],
    ]
];

// Common menu items for all roles
$commonItems = [
    ['icon' => 'bi-person', 'label' => 'My Profile', 'link' => '#'],
    ['icon' => 'bi-key', 'label' => 'Change Password', 'link' => '#'],
];
?>

<div class="sidebar-header">
    <div class="sidebar-brand">
        <i class="bi bi-hospital"></i>
        <span>Dental Care</span>
    </div>
</div>

<ul class="sidebar-nav">
    <?php if (isset($menuItems[$currentRole])): ?>
        <?php foreach ($menuItems[$currentRole] as $item): ?>
            <?php 
            // Determine if this menu item should be active
            $isActive = false;
            if ($item['link'] !== '#') {
                if (strpos($item['link'], 'modules') !== false) {
                    // For module links, check if current page contains the module path
                    $isActive = strpos($_SERVER['PHP_SELF'], $item['link']) !== false;
                } else {
                    // For dashboard links, check exact match
                    $isActive = $currentPage === $item['link'];
                }
            }
            ?>
            <li class="sidebar-item">
                <a href="<?php echo $item['link'] === '#' ? '#' : (strpos($item['link'], 'modules') !== false ? BASE_URL . $item['link'] : BASE_URL . 'dashboard/' . $item['link']); ?>" 
                   class="sidebar-link <?php echo $isActive ? 'active' : ''; ?>">
                    <i class="sidebar-icon <?php echo $item['icon']; ?>"></i>
                    <span class="sidebar-text"><?php echo $item['label']; ?></span>
                    <?php if ($item['link'] === '#'): ?>
                        <span class="badge bg-secondary ms-auto">Soon</span>
                    <?php endif; ?>
                </a>
            </li>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <li class="sidebar-divider"></li>
    
    <?php foreach ($commonItems as $item): ?>
        <li class="sidebar-item">
            <a href="<?php echo $item['link']; ?>" class="sidebar-link">
                <i class="sidebar-icon <?php echo $item['icon']; ?>"></i>
                <span class="sidebar-text"><?php echo $item['label']; ?></span>
            </a>
        </li>
    <?php endforeach; ?>
    
    <li class="sidebar-item">
        <a href="<?php echo BASE_URL; ?>modules/auth/logout.php" class="sidebar-link text-danger">
            <i class="sidebar-icon bi-box-arrow-right"></i>
            <span class="sidebar-text">Logout</span>
        </a>
    </li>
</ul>
