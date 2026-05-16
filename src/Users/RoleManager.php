<?php
namespace App\Users;

class RoleManager {
    /**
     * Check if the currently logged-in user has the required authorization level.
     * Use this at the top of your processing scripts/controllers.
     */
    public static function requireRole(string $requiredRole): void {
        // 1. Check if user is even logged in
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
            header('Location: ' . url('auth', null, ['error' => 'unauthorized']));
            exit();
        }

        // 2. Define role hierarchy values for granular access control
        $roles = [
            'crew'               => 1,
            'procurement_officer' => 2,
            'line_producer'      => 3,
            'executive_producer' => 4
        ];

        $userRole = $_SESSION['role'];

        // If the user's role doesn't exist or is lower than required, block them
        if (!isset($roles[$userRole]) || $roles[$userRole] < $roles[$requiredRole]) {
            // Log the unauthorized attempt for audit purposes (using error_log as fallback)
            error_log("Security Alert: User ID {$_SESSION['user_id']} attempted to access an unauthorized resource requiring role: {$requiredRole}");
            
            // Redirect to dashboard with an error message
            header('Location: ' . url('dashboard', null, ['error' => 'forbidden']));
            exit();
        }
    }

    /**
     * Helper to conditionally show UI buttons/elements based on permissions
     */
    public static function hasAccess(string $minimumRole): bool {
        if (!isset($_SESSION['role'])) return false;
        
        $roles = ['crew' => 1, 'procurement_officer' => 2, 'line_producer' => 3, 'executive_producer' => 4];
        return ($roles[$_SESSION['role']] ?? 0) >= ($roles[$minimumRole] ?? 0);
    }
}