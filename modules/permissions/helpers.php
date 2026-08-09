<?php
// Permissions Helper Functions

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/db.php';

/**
 * Check if a role has a specific permission
 * 
 * @param PDO $pdo Database connection
 * @param int $roleId Role ID
 * @param string $permissionKey Permission key to check
 * @return bool True if role has permission, false otherwise
 */
function hasPermission($pdo, $roleId, $permissionKey) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count 
                                FROM role_permissions rp 
                                JOIN permissions p ON rp.permission_id = p.id 
                                WHERE rp.role_id = ? AND p.permission_key = ?");
        $stmt->execute([$roleId, $permissionKey]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    } catch (PDOException $e) {
        error_log("Permission check error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all permission keys for a role (for session storage)
 * 
 * @param PDO $pdo Database connection
 * @param int $roleId Role ID
 * @return array Array of permission keys
 */
function getSessionPermissions($pdo, $roleId) {
    try {
        $stmt = $pdo->prepare("SELECT p.permission_key 
                                FROM role_permissions rp 
                                JOIN permissions p ON rp.permission_id = p.id 
                                WHERE rp.role_id = ?");
        $stmt->execute([$roleId]);
        $permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return $permissions;
    } catch (PDOException $e) {
        error_log("Get session permissions error: " . $e->getMessage());
        return [];
    }
}

/**
 * Check if current user has a specific permission (session-based)
 * 
 * @param string $permissionKey Permission key to check
 * @return bool True if user has permission, false otherwise
 */
function checkPermission($permissionKey) {
    if (!isset($_SESSION['permissions']) || !is_array($_SESSION['permissions'])) {
        return false;
    }
    return in_array($permissionKey, $_SESSION['permissions']);
}
?>
