<?php

namespace App\Auth;

class Permissions
{
    // Define roles and their hierarchy (higher number means more permissions)
    private const ROLE_HIERARCHY = [
        'STAFF'   => 1,
        'MANAGER' => 2,
        'ADMIN'   => 3,
    ];

    // Define permissions and which roles possess them
    private const ROLE_PERMISSIONS = [
        'ADMIN' => [
            'manage_inventory',
            'approve_request',
            'receive_goods',
            'view_reports',
            'manage_suppliers',
            'manage_users',
            'system_settings',
            'backup_restore',
        ],
        'MANAGER' => [
            'manage_inventory',
            'approve_request',
            'receive_goods',
            'view_reports',
            'manage_suppliers',
        ],
        'STAFF' => [
            'create_request',
            'view_my_requests',
        ],
    ];

    /**
     * Checks if a given role has a specific permission.
     *
     * @param string $userRole The role of the currently logged-in user (e.g., 'ADMIN', 'MANAGER').
     * @param string $permission The permission to check (e.g., 'manage_inventory').
     * @return bool True if the role has the permission, false otherwise.
     */
    public static function hasPermission(string $userRole, string $permission): bool
    {
        // Ensure the user role is valid
        if (!isset(self::ROLE_HIERARCHY[$userRole])) {
            return false;
        }

        // Check if the permission is explicitly granted to the user's role
        if (isset(self::ROLE_PERMISSIONS[$userRole]) && in_array($permission, self::ROLE_PERMISSIONS[$userRole])) {
            return true;
        }

        // For roles with a hierarchy, check if a higher role implicitly grants the permission
        // This is useful if a permission is only defined for ADMIN, but MANAGER should also have it.
        // However, with explicit ROLE_PERMISSIONS, this might be less necessary.
        // For now, we rely on explicit definition.

        return false;
    }

    /**
     * Checks if the current user's role meets or exceeds a minimum required role level.
     * This is useful for general access control based on hierarchy.
     *
     * @param string $userRole The role of the currently logged-in user.
     * @param string $minimumRequiredRole The minimum role level required.
     * @return bool True if the user's role is equal to or higher than the minimum required role.
     */
    public static function isAtLeast(string $userRole, string $minimumRequiredRole): bool
    {
        return (self::ROLE_HIERARCHY[$userRole] ?? 0) >= (self::ROLE_HIERARCHY[$minimumRequiredRole] ?? 0);
    }
}