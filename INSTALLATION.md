# Dental Management System - Installation Guide

## For End Users

### Quick Installation

1. **Upload Files**
   - Upload the entire `dental-mgt` folder to your web server (e.g., `/public_html/` or a subdirectory)
   - Ensure the folder structure remains intact

2. **Run the Installer**
   - Open your browser and navigate to: `http://yourdomain.com/dental-mgt/installer/`
   - If you installed in the root: `http://yourdomain.com/installer/`

3. **Step 1: Requirements Check**
   - The installer will automatically check your server requirements
   - Fix any red (critical) errors before proceeding
   - Yellow warnings are optional but recommended

4. **Step 2: Database Configuration**
   - Enter your MySQL database connection details:
     - **Host**: Usually `localhost`
     - **Port**: Usually `3306`
     - **Database Name**: The name of the database you want to create
     - **Username**: Your MySQL username
     - **Password**: Your MySQL password
   - Click **"Test Connection"** to verify credentials
   - Upload the provided SQL file (`dental_management_db.sql`)
   - Click **"Next"** when connection is successful

5. **Step 3: Admin Account Setup**
   - **Application Name**: The name for your dental clinic (e.g., "ABC Dental Clinic")
   - **Admin Full Name**: Your name
   - **Admin Email**: Your email address (this will be your login username)
   - **Admin Password**: Create a strong password (minimum 8 characters, must include letters and numbers)
   - Click **"Next"**

6. **Step 4: Install**
   - Review the configuration summary
   - Click **"Install Now"** to begin the installation
   - Wait for the installation to complete
   - Upon success, click **"Go to Login"**

7. **Post-Installation**
   - **IMPORTANT**: Delete the `/installer` folder from your server for security
   - Log in with your admin email and password
   - Start using the system!

### Server Requirements

- **PHP Version**: 8.0 or higher
- **PHP Extensions**:
  - `pdo` (Required)
  - `pdo_mysql` (Required)
  - `fileinfo` (Recommended)
  - `gd` (Recommended)
- **MySQL**: 5.7 or higher / MariaDB 10.2 or higher
- **Web Server**: Apache (recommended) or Nginx
- **Disk Space**: Minimum 50MB free space
- **Write Permissions**:
  - `/config` folder (writable)
  - `/logs` folder (writable)
  - `/installer/temp` folder (writable)

### Troubleshooting

**Issue: "Access denied" when testing database connection**
- Verify your MySQL username and password are correct
- Ensure the user has CREATE DATABASE privileges

**Issue: "Cannot connect to MySQL server"**
- Check that MySQL/MariaDB is running
- Verify the host and port are correct
- If using a remote database, ensure your server allows remote connections

**Issue: "Config folder is not writable"**
- Run: `chmod 755 config/` (Linux/Mac)
- For Windows, ensure the folder is not read-only

**Issue: "File upload failed"**
- Check PHP `upload_max_filesize` and `post_max_size` in php.ini
- The SQL file should be under the upload limit

**Issue: Already installed message appears**
- To reinstall, delete the file: `/config/installed.lock`
- **Warning**: This will reset your installation

### Security Best Practices

1. **Delete the installer folder** after successful installation
2. **Use strong passwords** for admin accounts
3. **Keep PHP and MySQL updated** to the latest versions
4. **Enable HTTPS/SSL** for production use
5. **Regular backups** of your database

---

## For Developer: Database Export Instructions

### Before Each Release

When preparing a new release of the Dental Management System, you must export the complete database (structure + seed data) into a single SQL file that end-users will upload during installation.

### Export Procedure

1. **Ensure Database is in Clean State**
   - All tables should be present with proper structure
   - Seed data should be representative of a fresh installation
   - No sensitive user data should be included
   - Clear any test/development data

2. **Export Using mysqldump (Recommended)**

   **Linux/Mac:**
   ```bash
   mysqldump -u root -p dental_management_db > database/dental_management_db.sql
   ```

   **Windows (XAMPP):**
   ```cmd
   C:\xampp\mysql\bin\mysqldump.exe -u root dental_management_db > database\dental_management_db.sql
   ```

   **Parameters:**
   - `-u root`: MySQL username (adjust as needed)
   - `dental_management_db`: Your database name
   - `database/dental_management_db.sql`: Output file path

3. **Verify the Exported File**
   - Check that the file contains:
     - `CREATE TABLE` statements for all tables
     - `INSERT` statements for seed data
     - No sensitive production data
   - File size should be reasonable (typically 1-5MB for seed data)

4. **Include in Release Package**
   - Place the SQL file in the `/database/` folder
   - Ensure it's named exactly: `dental_management_db.sql`
   - The installer will look for this specific filename

### What Should Be in the SQL File

**Tables to Include:**
- `activity_logs` (empty structure only)
- `appointments` (empty structure only)
- `invoice_items` (empty structure only)
- `invoices` (empty structure only)
- `patients` (empty structure only)
- `payments` (empty structure only)
- `permissions` (with all permission keys)
- `prescriptions` (empty structure only)
- `role_permissions` (with default role-permission mappings)
- `roles` (with all 4 roles: Admin, Doctor, Receptionist, Patient)
- `treatment_items` (empty structure only)
- `treatment_records` (empty structure only)
- `users` (empty structure only - installer will create admin)

**Seed Data to Include:**
- All roles (Admin, Doctor, Receptionist, Patient)
- All 22 permissions (Patients, Appointments, Treatments, Billing, Reports, Users modules)
- Default role-permission mappings (45 total):
  - Admin: All 22 permissions
  - Doctor: 7 permissions
  - Receptionist: 16 permissions
  - Patient: 0 permissions

**What NOT to Include:**
- Any user accounts (installer creates admin)
- Any patient data
- Any appointment data
- Any billing/invoice data
- Any treatment records
- Any activity logs

### Testing the Export

After exporting the SQL file:

1. **Test Fresh Installation**
   - Drop the test database
   - Run the installer with the exported SQL file
   - Verify all tables are created correctly
   - Verify seed data is present
   - Verify admin account is created
   - Test login functionality

2. **Verify Permission System**
   - Login as admin
   - Check permission management page
   - Verify all 22 permissions are present
   - Verify role-permission mappings are correct

### Example Complete Export Command

For a comprehensive export that includes everything needed:

```bash
mysqldump -u root -p \
  --single-transaction \
  --routines \
  --triggers \
  --no-data \
  dental_management_db > database/dental_management_db_structure.sql

mysqldump -u root -p \
  --single-transaction \
  --no-create-info \
  --ignore-table=dental_management_db.users \
  --ignore-table=dental_management_db.patients \
  --ignore-table=dental_management_db.appointments \
  --ignore-table=dental_management_db.treatment_records \
  --ignore-table=dental_management_db.invoices \
  --ignore-table=dental_management_db.payments \
  --ignore-table=dental_management_db.activity_logs \
  dental_management_db > database/dental_management_db_data.sql

# Combine them
cat database/dental_management_db_structure.sql database/dental_management_db_data.sql > database/dental_management_db.sql
```

Or use the simpler approach (recommended for this project):

```bash
mysqldump -u root -p dental_management_db > database/dental_management_db.sql
```

Then manually remove any sensitive data from the SQL file before packaging.

---

## Installation File Structure

```
dental-mgt/
├── config/
│   ├── constants.php          (generated by installer)
│   ├── db.php                 (static, reads from constants.php)
│   └── installed.lock         (created by installer)
├── database/
│   └── dental_management_db.sql (uploaded by user during install)
├── installer/
│   ├── bootstrap.php          (shared constants & session handling)
│   ├── index.php              (main router)
│   ├── step1.php              (requirements check)
│   ├── step2.php              (database config + SQL upload)
│   ├── test-connection.php    (AJAX endpoint)
│   ├── step3.php              (admin account setup)
│   ├── step4.php              (install summary)
│   ├── process.php            (AJAX install handler)
│   └── temp/
│       └── .htaccess          (deny all access)
├── logs/                      (created if missing)
├── modules/                   (application modules)
├── dashboard/                 (dashboard files)
├── assets/                    (CSS, JS, images)
└── index.php                  (main entry point - checks for installed.lock)
```

---

## Support

For issues or questions:
- Check the troubleshooting section above
- Review server requirements
- Ensure all file permissions are correct
- Verify database credentials
