<?php
/**
 * Global helper functions for the Film Studio application.
 */

/**
 * Generates a clean URL for routing via the front controller.
 * 
 * @param string $page The target page name.
 * @param string|null $action The specific action to perform.
 * @param array $params Additional query parameters (e.g., ['id' => 1]).
 * @return string The formatted URL.
 */
function url(string $page = 'dashboard', ?string $action = null, array $params = []): string {
    $data = ['page' => $page];
    if ($action !== null) $data['action'] = $action;
    if ($params) $data = array_merge($data, $params);
    
    // Ensure a clean front-controller query string without physical file extensions
    return 'index.php?' . http_build_query($data);
}