<?php

namespace App\Auth;

class Permissions
{
    // Define roles and their hierarchy (higher number means more permissions)
    private const ROLE_HIERARCHY = [
        'crew'                => 1,
        'STAFF'               => 1, // Kept for legacy compatibility
        'procurement_officer' => 2,
        'line_producer'       => 3,
        'MANAGER'             => 3, // Kept for legacy compatibility
        'executive_producer'  => 4,
        'ADMIN'               => 5,
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
        'executive_producer' => [
            'manage_inventory',
            'approve_request',
            'receive_goods',
            'view_reports',
            'manage_suppliers',
            'create_request',
            'view_my_requests',
        ],
        'MANAGER' => [
            'manage_inventory',
            'approve_request',
            'receive_goods',
            'view_reports',
            'manage_suppliers',
        ],
        'line_producer' => [
            'manage_inventory',
            'approve_request',
            'create_request',
            'view_my_requests',
        ],
        'procurement_officer' => [
            'manage_inventory',
            'receive_goods',
            'manage_suppliers',
            'create_request',
            'view_my_requests',
        ],
        'crew' => [
            'create_request',
            'view_my_requests',
        ],
        'STAFF' => [
            'create_request',
            'view_my_requests',
        ],
    ];

    /**
     * Checks if a given role has a specific permission.
     *
     * @param string $userRole The role of the currently logged-in user (e.g., 'ADMIN', 'crew').
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