<?php
/**
 * AJAX endpoint: Get active departments.
 *
 * Requires a logged-in staff user (for security, not public).
 */
require_once __DIR__ . '/../includes/functions.php';

// Must be a logged-in staff user
if (empty($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode([]);
    exit;
}

$departments = array_values(array_filter(get_all_departments($pdo), static fn(array $department): bool => (bool) $department['is_active']));
echo json_encode($departments);
