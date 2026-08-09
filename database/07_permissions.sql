-- Database: dental_management_db
-- Script: 07_permissions.sql
-- Description: Create permissions and role_permissions tables for dynamic permission management

-- Table: permissions
CREATE TABLE IF NOT EXISTS permissions (
    id INT(11) NOT NULL AUTO_INCREMENT,
    permission_key VARCHAR(100) NOT NULL,
    module_name VARCHAR(50) NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_permission_key (permission_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: role_permissions
CREATE TABLE IF NOT EXISTS role_permissions (
    id INT(11) NOT NULL AUTO_INCREMENT,
    role_id INT(11) NOT NULL,
    permission_id INT(11) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_role_permission (role_id, permission_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed permissions table with permission keys based on current hardcoded behavior
INSERT INTO permissions (permission_key, module_name, description) VALUES
-- Patients module
('patients.view', 'Patients', 'View patient list and details'),
('patients.add', 'Patients', 'Add new patients'),
('patients.edit', 'Patients', 'Edit patient information'),
('patients.delete', 'Patients', 'Delete patients'),

-- Appointments module
('appointments.view', 'Appointments', 'View appointment list and details'),
('appointments.add', 'Appointments', 'Book new appointments'),
('appointments.edit', 'Appointments', 'Edit appointment information'),
('appointments.cancel', 'Appointments', 'Cancel appointments'),

-- Treatments module
('treatments.view', 'Treatments', 'View treatment records'),
('treatments.add', 'Treatments', 'Add treatment records'),
('treatments.edit', 'Treatments', 'Edit treatment records'),

-- Billing module
('billing.view', 'Billing', 'View invoices and billing information'),
('billing.add_invoice', 'Billing', 'Create new invoices'),
('billing.edit_invoice', 'Billing', 'Edit invoice information'),
('billing.record_payment', 'Billing', 'Record payments'),

-- Reports module
('reports.patient', 'Reports', 'View patient reports'),
('reports.appointment', 'Reports', 'View appointment reports'),
('reports.treatment', 'Reports', 'View treatment reports'),
('reports.revenue', 'Reports', 'View revenue reports'),
('reports.payment', 'Reports', 'View payment reports'),
('reports.due_payment', 'Reports', 'View due payment reports'),

-- Users module
('users.manage', 'Users', 'Manage user accounts');

-- Seed role_permissions table to match current hardcoded behavior
-- Admin gets all permissions
INSERT INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions;

-- Doctor gets limited permissions
INSERT INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions WHERE permission_key IN (
    'patients.view',
    'appointments.view',
    'treatments.view',
    'treatments.add',
    'treatments.edit',
    'reports.patient',
    'reports.treatment'
);

-- Receptionist gets operational permissions
INSERT INTO role_permissions (role_id, permission_id)
SELECT 3, id FROM permissions WHERE permission_key IN (
    'patients.view',
    'patients.add',
    'patients.edit',
    'patients.delete',
    'appointments.view',
    'appointments.add',
    'appointments.edit',
    'appointments.cancel',
    'treatments.view',
    'billing.view',
    'billing.add_invoice',
    'billing.edit_invoice',
    'billing.record_payment',
    'reports.appointment',
    'reports.payment',
    'reports.due_payment'
);

-- Patient gets no permissions (empty set - no role_permissions entries)
