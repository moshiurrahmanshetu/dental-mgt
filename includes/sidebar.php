<?php
// Get current role (session already started in auth_check.php)
$currentRole = $_SESSION['role_name'] ?? '';
$currentPage = basename($_SERVER['PHP_SELF']);

// Define menu items based on role
$menuItems = [
    'Admin' => [
        ['icon' => 'bi-speedometer2', 'label' => 'Dashboard', 'link' => 'admin.php'],
        ['icon' => 'bi-people', 'label' => 'Patients', 'link' => '#', 'submenu' => [
            ['label' => 'All Patients', 'link' => '../modules/patients/list.php'],
            ['label' => 'Add Patient', 'link' => '../modules/patients/add.php']
        ]],
        ['icon' => 'bi-calendar-check', 'label' => 'Appointments', 'link' => '#', 'submenu' => [
            ['label' => 'All Appointments', 'link' => '../modules/appointments/list.php'],
            ['label' => "Today's Appointments", 'link' => '../modules/appointments/today.php'],
            ['label' => 'Upcoming Appointments', 'link' => '../modules/appointments/upcoming.php'],
            ['label' => 'Book Appointment', 'link' => '../modules/appointments/add.php']
        ]],
        ['icon' => 'bi-journal-medical', 'label' => 'Treatment Records', 'link' => '#', 'submenu' => [
            ['label' => 'All Records', 'link' => '../modules/treatments/list.php']
        ]],
        ['icon' => 'bi-currency-dollar', 'label' => 'Billing & Payments', 'link' => '#', 'submenu' => [
            ['label' => 'Invoices', 'link' => '../modules/billing/list-invoices.php'],
            ['label' => 'Payments', 'link' => '../modules/billing/payment-history.php'],
            ['label' => 'Due Payments', 'link' => '../modules/billing/due-payments.php']
        ]],
        ['icon' => 'bi-bar-chart', 'label' => 'Reports', 'link' => '#', 'submenu' => [
            ['label' => 'Patient Report', 'link' => '../modules/reports/patient-report.php'],
            ['label' => 'Appointment Report', 'link' => '../modules/reports/appointment-report.php'],
            ['label' => 'Treatment Report', 'link' => '../modules/reports/treatment-report.php'],
            ['label' => 'Revenue Report', 'link' => '../modules/reports/revenue-report.php'],
            ['label' => 'Payment Report', 'link' => '../modules/reports/payment-report.php'],
            ['label' => 'Due Payment Report', 'link' => '../modules/reports/due-payment-report.php']
        ]],
        ['icon' => 'bi-person-gear', 'label' => 'User Management', 'link' => '#', 'submenu' => [
            ['label' => 'Users', 'link' => '../modules/users/list.php'],
            ['label' => 'Roles', 'link' => '../modules/users/roles.php']
        ]],
    ],
    'Doctor' => [
        ['icon' => 'bi-speedometer2', 'label' => 'Dashboard', 'link' => 'doctor.php'],
        ['icon' => 'bi-people', 'label' => 'My Patients', 'link' => '../modules/patients/list.php'],
        ['icon' => 'bi-calendar-check', 'label' => 'Appointments', 'link' => '#', 'submenu' => [
            ['label' => 'All Appointments', 'link' => '../modules/appointments/list.php'],
            ['label' => "Today's Appointments", 'link' => '../modules/appointments/today.php'],
            ['label' => 'Upcoming Appointments', 'link' => '../modules/appointments/upcoming.php']
        ]],
        ['icon' => 'bi-journal-medical', 'label' => 'Treatment Records', 'link' => '#', 'submenu' => [
            ['label' => 'My Records', 'link' => '../modules/treatments/list.php']
        ]],
        ['icon' => 'bi-currency-dollar', 'label' => 'Billing', 'link' => '#', 'submenu' => [
            ['label' => 'View Invoices', 'link' => '../modules/billing/list-invoices.php'],
            ['label' => 'Payment History', 'link' => '../modules/billing/payment-history.php']
        ]],
        ['icon' => 'bi-bar-chart', 'label' => 'Reports', 'link' => '#', 'submenu' => [
            ['label' => 'Patient Report', 'link' => '../modules/reports/patient-report.php'],
            ['label' => 'Treatment Report', 'link' => '../modules/reports/treatment-report.php']
        ]],
    ],
    'Receptionist' => [
        ['icon' => 'bi-speedometer2', 'label' => 'Dashboard', 'link' => 'receptionist.php'],
        ['icon' => 'bi-people', 'label' => 'Patients', 'link' => '#', 'submenu' => [
            ['label' => 'All Patients', 'link' => '../modules/patients/list.php'],
            ['label' => 'Add Patient', 'link' => '../modules/patients/add.php']
        ]],
        ['icon' => 'bi-calendar-check', 'label' => 'Appointments', 'link' => '#', 'submenu' => [
            ['label' => 'All Appointments', 'link' => '../modules/appointments/list.php'],
            ['label' => "Today's Appointments", 'link' => '../modules/appointments/today.php'],
            ['label' => 'Upcoming Appointments', 'link' => '../modules/appointments/upcoming.php'],
            ['label' => 'Book Appointment', 'link' => '../modules/appointments/add.php']
        ]],
        ['icon' => 'bi-journal-medical', 'label' => 'Treatment Records', 'link' => '#', 'submenu' => [
            ['label' => 'View Records', 'link' => '../modules/treatments/list.php']
        ]],
        ['icon' => 'bi-currency-dollar', 'label' => 'Billing & Payments', 'link' => '#', 'submenu' => [
            ['label' => 'Invoices', 'link' => '../modules/billing/list-invoices.php'],
            ['label' => 'Payments', 'link' => '../modules/billing/payment-history.php'],
            ['label' => 'Due Payments', 'link' => '../modules/billing/due-payments.php']
        ]],
        ['icon' => 'bi-bar-chart', 'label' => 'Reports', 'link' => '#', 'submenu' => [
            ['label' => 'Appointment Report', 'link' => '../modules/reports/appointment-report.php'],
            ['label' => 'Payment Report', 'link' => '../modules/reports/payment-report.php']
        ]],
    ],
    'Patient' => [
        ['icon' => 'bi-speedometer2', 'label' => 'Dashboard', 'link' => 'patient.php'],
        ['icon' => 'bi-calendar-check', 'label' => 'My Appointments', 'link' => '#'],
        ['icon' => 'bi-journal-medical', 'label' => 'Medical History', 'link' => '#'],
        ['icon' => 'bi-currency-dollar', 'label' => 'My Bills', 'link' => '#'],
    ]
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
            $hasSubmenu = isset($item['submenu']);
            
            if ($hasSubmenu) {
                // Check if any submenu item is active
                foreach ($item['submenu'] as $subItem) {
                    if (strpos($_SERVER['PHP_SELF'], $subItem['link']) !== false) {
                        $isActive = true;
                        break;
                    }
                }
            } elseif ($item['link'] !== '#') {
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
                <?php if ($hasSubmenu): ?>
                    <a href="#" class="sidebar-link <?php echo $isActive ? 'active' : ''; ?>" data-bs-toggle="collapse" data-bs-target="#submenu-<?php echo md5($item['label']); ?>">
                        <i class="sidebar-icon <?php echo $item['icon']; ?>"></i>
                        <span class="sidebar-text"><?php echo $item['label']; ?></span>
                        <i class="bi bi-chevron-down ms-auto sidebar-arrow"></i>
                    </a>
                    <ul class="sidebar-submenu collapse <?php echo $isActive ? 'show' : ''; ?>" id="submenu-<?php echo md5($item['label']); ?>">
                        <?php foreach ($item['submenu'] as $subItem): ?>
                            <?php 
                            $isSubActive = strpos($_SERVER['PHP_SELF'], $subItem['link']) !== false;
                            // Hide "Book Appointment" for Doctor role
                            if ($currentRole === 'Doctor' && strpos($subItem['link'], 'appointments/add.php') !== false) {
                                continue;
                            }
                            // Hide "Add Patient" for Doctor role
                            if ($currentRole === 'Doctor' && strpos($subItem['link'], 'patients/add.php') !== false) {
                                continue;
                            }
                            ?>
                            <li class="sidebar-item">
                                <a href="<?php echo BASE_URL . $subItem['link']; ?>" 
                                   class="sidebar-link <?php echo $isSubActive ? 'active' : ''; ?>">
                                    <span class="sidebar-text"><?php echo $subItem['label']; ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <a href="<?php echo $item['link'] === '#' ? '#' : (strpos($item['link'], 'modules') !== false ? BASE_URL . $item['link'] : BASE_URL . 'dashboard/' . $item['link']); ?>" 
                       class="sidebar-link <?php echo $isActive ? 'active' : ''; ?>">
                        <i class="sidebar-icon <?php echo $item['icon']; ?>"></i>
                        <span class="sidebar-text"><?php echo $item['label']; ?></span>
                        <?php if ($item['link'] === '#'): ?>
                            <span class="badge bg-secondary ms-auto">Soon</span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    <?php endif; ?>
</ul>
