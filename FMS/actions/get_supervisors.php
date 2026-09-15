<?php
/** AJAX endpoint: Get active supervisors. */
require_once __DIR__ . '/../includes/functions.php';

if (empty($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode([]);
    exit;
}

echo json_encode(get_supervisors($pdo));
