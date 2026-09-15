<?php
require_once __DIR__ . '/database.php';

/**
 * Escape output for safe HTML display.
 * This prevents Cross-Site Scripting (XSS) by converting special
 * characters to HTML entities.
 */
function e($value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}
function verify_csrf(): bool
{
    $token = $_POST['csrf_token'] ?? '';
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Store a one-time "flash" message in the session.
 * The message is displayed once on the next page load, then cleared.
 */
function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Retrieve and clear the flash message.
 * Returns null if no message is set.
 */
function get_flash(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
function redirect(string $path): void
{
    header("Location: " . $path);
    exit;
}
function generate_reference_number(): string
{
    return 'FMS-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(5)), 0, 10));
}
function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Get the user's browser user agent string for audit logging.
 * Truncated to fit the database column length (255 chars).
 */
function user_agent(): string
{
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    return substr($ua, 0, 255);
}


function user_has_role(PDO $pdo, int $userId, string $roleName): bool
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM user_roles ur
        JOIN roles r ON ur.role_id = r.id
        WHERE ur.user_id = ? AND r.name = ?
    ");
    $stmt->execute([$userId, $roleName]);
    return $stmt->fetchColumn() > 0;
}


function user_has_permission(PDO $pdo, int $userId, string $permissionDesc): bool
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM user_roles ur
        JOIN role_permissions rp ON ur.role_id = rp.role_id
        JOIN permissions p ON rp.permission_id = p.id
        WHERE ur.user_id = ? AND p.description = ?
    ");
    $stmt->execute([$userId, $permissionDesc]);
    return $stmt->fetchColumn() > 0;
}

function render_breadcrumbs($current_page_title, $parent_page = null, $parent_url = '#') {
    ?>
    <div class="col-sm-6">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb float-sm-end float-sm-right">
                <li class="breadcrumb-item"><a href="/FMS/index.php">Home</a></li>
                <?php if ($parent_page): ?>
                    <li class="breadcrumb-item"><a href="<?= $parent_url ?>"><?= htmlspecialchars($parent_page) ?></a></li>
                <?php endif; ?>
                <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($current_page_title) ?></li>
            </ol>
        </nav>
    </div>
    <?php
}
function get_user_roles(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare("
        SELECT r.name FROM user_roles ur
        JOIN roles r ON ur.role_id = r.id
        WHERE ur.user_id = ?
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}


function log_application_action(PDO $pdo, int $applicationId, ?int $userId, string $action, ?array $oldValues = null, ?array $newValues = null): void {
    $stmt = $pdo->prepare("
        INSERT INTO application_audit_logs
            (application_id, user_id, action, old_values, new_values, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $applicationId,
        $userId,
        $action,
        $oldValues !== null ? json_encode($oldValues) : null, /*Convert a PHP array/object into JSON text. If the value is null, store null in the database.*/
        $newValues !== null ? json_encode($newValues) : null,
        client_ip(),
        user_agent(),
    ]);
}

/**
 * Create an audit log entry for a placement action.
 */
function log_placement_action(
    PDO $pdo,
    int $placementId,
    ?int $userId,
    string $action,
    ?array $oldValues = null,
    ?array $newValues = null
): void {
    $stmt = $pdo->prepare("
        INSERT INTO placement_audit_logs
            (placement_id, user_id, action, old_values, new_values, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $placementId,
        $userId,
        $action,
        $oldValues !== null ? json_encode($oldValues) : null,
        $newValues !== null ? json_encode($newValues) : null,
        client_ip(),
        user_agent(),
    ]);
}

/**
 * Create a comment linked to an application (polymorphic comments table).
 * commentable_type is 'application' for application comments.
 */
function add_comment(
    PDO $pdo,
    ?int $userId,
    ?int $studentId,
    string $commentableType,
    int $commentableId,
    string $commentText
): int {
    $stmt = $pdo->prepare("
        INSERT INTO comments (user_id, student_id, commentable_type, commentable_id, comment_text)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$userId, $studentId, $commentableType, $commentableId, $commentText]);
    return (int) $pdo->lastInsertId();
}

/**
 * Get all comments for a given commentable entity (e.g. an application).
 */
function get_comments(PDO $pdo, string $commentableType, int $commentableId): array
{
    $stmt = $pdo->prepare("
        SELECT c.*, 
               u.full_name AS staff_name,
               s.full_name AS student_name
        FROM comments c
        LEFT JOIN users u ON c.user_id = u.id
        LEFT JOIN students s ON c.student_id = s.id
        WHERE c.commentable_type = ? AND c.commentable_id = ?
        ORDER BY c.created_at ASC
    ");
    $stmt->execute([$commentableType, $commentableId]);
    return $stmt->fetchAll();
}


function get_application_audit_logs(PDO $pdo, int $applicationId): array
{
    $stmt = $pdo->prepare("
        SELECT al.*, u.full_name AS user_name
        FROM application_audit_logs al
        LEFT JOIN users u ON al.user_id = u.id
        WHERE al.application_id = ?
        ORDER BY al.created_at DESC
    ");
    $stmt->execute([$applicationId]);
    return $stmt->fetchAll();
}


function get_placement_audit_logs(PDO $pdo, int $placementId): array
{
    $stmt = $pdo->prepare("
        SELECT al.*, u.full_name AS user_name
        FROM placement_audit_logs al
        LEFT JOIN users u ON al.user_id = u.id
        WHERE al.placement_id = ?
        ORDER BY al.created_at DESC
    ");
    $stmt->execute([$placementId]);
    return $stmt->fetchAll();
}


function get_application_status_counts(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT status, COUNT(*) AS cnt
        FROM applications
        GROUP BY status
    ");
    $results = $stmt->fetchAll();
    $counts = [];
    foreach ($results as $row) {
        $counts[$row['status']] = (int) $row['cnt'];
    }
    return $counts;
}

/**
 * Get the active application windows for display.
 */
function get_active_application_windows(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT * FROM application_windows
        WHERE is_active = 1
          AND open_date <= NOW()
          AND close_date >= NOW()
        ORDER BY close_date ASC
    ");
    return $stmt->fetchAll();
}

/**
 * Get all application windows (for admin management).
 */
function get_all_application_windows(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT aw.*, u.full_name AS creator_name
        FROM application_windows aw
        LEFT JOIN users u ON aw.created_by = u.id
        ORDER BY aw.open_date DESC
    ");
    return $stmt->fetchAll();
}


function get_all_departments(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT * FROM departments ORDER BY name ASC");
    return $stmt->fetchAll();
}


function get_specializations(PDO $pdo, bool $activeOnly = true): array
{
    $sql = "SELECT * FROM specializations";
    if ($activeOnly) {
        $sql .= " WHERE is_active = 1";
    }
    $sql .= " ORDER BY name ASC";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}


function get_application_specializations(PDO $pdo, int $applicationId): array
{
    $stmt = $pdo->prepare("
        SELECT s.* FROM application_specializations aps
        JOIN specializations s ON aps.specialization_id = s.id
        WHERE aps.application_id = ?
    ");
    $stmt->execute([$applicationId]);
    return $stmt->fetchAll();
}


function get_training_types(PDO $pdo, bool $activeOnly = true): array
{
    $sql = "SELECT * FROM training_types";
    if ($activeOnly) {
        $sql .= " WHERE is_active = 1";
    }
    $sql .= " ORDER BY name ASC";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}


function get_study_levels(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT * FROM study_levels ORDER BY name ASC");
    return $stmt->fetchAll();
}


function get_nationalities(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT * FROM nationalities ORDER BY name ASC");
    return $stmt->fetchAll();
}


function get_supervisors(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT u.* FROM users u
        JOIN user_roles ur ON u.id = ur.user_id
        JOIN roles r ON ur.role_id = r.id
        WHERE r.name IN ('academic_supervisor', 'industrial_supervisor', 'supervisor')
          AND u.status = 'active'
        ORDER BY u.full_name ASC
    ");
    return $stmt->fetchAll();
}


function get_all_users(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT u.*, GROUP_CONCAT(r.name SEPARATOR ', ') AS role_names
        FROM users u
        LEFT JOIN user_roles ur ON u.id = ur.user_id
        LEFT JOIN roles r ON ur.role_id = r.id
        WHERE u.deleted_at IS NULL
        GROUP BY u.id
        ORDER BY u.full_name ASC
    ");
    return $stmt->fetchAll();
}


function get_all_students(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT * FROM students ORDER BY full_name ASC");
    return $stmt->fetchAll();
}


function get_student(PDO $pdo, int $studentId): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$studentId]);
    $result = $stmt->fetch();
    return $result ?: null;
}


function get_user(PDO $pdo, int $userId): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    return $result ?: null;
}


function get_application(PDO $pdo, int $applicationId): ?array
{
    $stmt = $pdo->prepare("
        SELECT a.*, 
               s.full_name AS student_name,
               s.registration_no,
               s.email AS student_email,
               s.course_of_study,
               s.gender,
               tw.name AS window_name,
               tt.name AS training_type_name,
               sl.name AS study_level_name
        FROM applications a
        JOIN students s ON a.student_id = s.id
        LEFT JOIN application_windows tw ON a.application_window_id = tw.id
        LEFT JOIN training_types tt ON a.training_type_id = tt.id
        LEFT JOIN study_levels sl ON a.study_level_id = sl.id
        WHERE a.id = ?
    ");
    $stmt->execute([$applicationId]);
    $result = $stmt->fetch();
    return $result ?: null;
}


function get_placement(PDO $pdo, int $placementId): ?array
{
    $stmt = $pdo->prepare("
        SELECT p.*,
               a.reference_number,
               s.full_name AS student_name,
               s.registration_no,

               d.name AS department_name,
               au.full_name AS academic_supervisor_name,
               iu.full_name AS industrial_supervisor_name
        FROM placements p
        JOIN applications a ON p.application_id = a.id
        JOIN students s ON a.student_id = s.id
        JOIN departments d ON p.department_id = d.id
        LEFT JOIN users au ON p.academic_supervisor_id = au.id
        LEFT JOIN users iu ON p.industrial_supervisor_id = iu.id
        WHERE p.id = ?
    ");
    $stmt->execute([$placementId]);
    $result = $stmt->fetch();
    return $result ?: null;
}


function get_placement_by_application(PDO $pdo, int $applicationId): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM placements WHERE application_id = ?");
    $stmt->execute([$applicationId]);
    $result = $stmt->fetch();
    return $result ?: null;
}


function get_placement_by_student(PDO $pdo, int $studentId): ?array
{
    $stmt = $pdo->prepare("
        SELECT p.*,
               a.reference_number,
             
               d.name AS department_name,
               au.full_name AS academic_supervisor_name,
               iu.full_name AS industrial_supervisor_name
        FROM placements p
        JOIN applications a ON p.application_id = a.id
        JOIN departments d ON p.department_id = d.id
        LEFT JOIN users au ON p.academic_supervisor_id = au.id
        LEFT JOIN users iu ON p.industrial_supervisor_id = iu.id
        WHERE a.student_id = ?
        ORDER BY p.created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$studentId]);
    $result = $stmt->fetch();
    return $result ?: null;
}


function get_application_reviews(PDO $pdo, int $applicationId): array
{
    $stmt = $pdo->prepare("
        SELECT r.*, u.full_name AS reviewer_name
        FROM reviews r
        JOIN users u ON r.user_id = u.id
        WHERE r.application_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$applicationId]);
    return $stmt->fetchAll();
}


function get_student_applications(PDO $pdo, int $studentId): array
{
    $stmt = $pdo->prepare("
        SELECT a.*,
               tw.name AS window_name,
               tt.name AS training_type_name,
               sl.name AS study_level_name
        FROM applications a
        LEFT JOIN application_windows tw ON a.application_window_id = tw.id
        LEFT JOIN training_types tt ON a.training_type_id = tt.id
        LEFT JOIN study_levels sl ON a.study_level_id = sl.id
        WHERE a.student_id = ?
        ORDER BY a.created_at DESC
    ");
    $stmt->execute([$studentId]);
    return $stmt->fetchAll();
}


function get_student_notifications(PDO $pdo, int $studentId): array
{
    $notifications = [];
    $statusMessages = [
        'submitted'               => 'Your application %s has been submitted and is pending review.',
        'under_review'            => 'Your application %s is now under review.',
        'returned_for_correction' => 'Your application %s has been returned for correction. Please review and resubmit.',
        'accepted'                => 'Your application %s has been accepted!',
        'rejected'                => 'Your application %s has been rejected.',
        'placement_assigned'      => 'A placement has been assigned for your application %s. Check the My Placement page for details.',
    ];

    foreach (get_student_applications($pdo, $studentId) as $app) {
        if (isset($statusMessages[$app['status']])) {
            $notifications[] = [
                'type' => $app['status'],
                'message' => sprintf($statusMessages[$app['status']], $app['reference_number']),
                'date' => $app['updated_at'],
            ];
        }

        foreach (get_comments($pdo, 'application', (int) $app['id']) as $comment) {
            if (!empty($comment['user_id'])) {
                $notifications[] = [
                    'type' => 'comment',
                    'message' => 'New comment on ' . $app['reference_number'] . ': "' . $comment['comment_text'] . '"',
                    'date' => $comment['created_at'],
                ];
            }
        }
    }

    usort($notifications, fn($a, $b) => strtotime($b['date']) <=> strtotime($a['date']));
    return $notifications;
}


function get_all_roles(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT * FROM roles ORDER BY name ASC");
    return $stmt->fetchAll();
}


function get_user_role_ids(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare("SELECT role_id FROM user_roles WHERE user_id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}


function count_students(PDO $pdo): int
{
    return (int) $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
}


function count_applications(PDO $pdo): int
{
    return (int) $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();
}


function count_placements(PDO $pdo): int
{
    return (int) $pdo->query("SELECT COUNT(*) FROM placements")->fetchColumn();
}

function count_active_windows(PDO $pdo): int
{
    return (int) $pdo->query("
        SELECT COUNT(*) FROM application_windows
        WHERE is_active = 1 AND open_date <= NOW() AND close_date >= NOW()
    ")->fetchColumn();
}


function format_date(?string $datetime, string $format = 'M j, Y'): string
{
    if (empty($datetime)) return '—';
    $ts = strtotime($datetime);
    return $ts ? date($format, $ts) : '—';
}


function format_datetime(?string $datetime): string
{
    return format_date($datetime, 'M j, Y g:i A');
}


function status_badge_class(string $status): string
{
    return match ($status) {
        'draft'                   => 'bg-secondary',
        'submitted'               => 'bg-info',
        'under_review'            => 'bg-warning',
        'returned_for_correction' => 'bg-warning text-dark',
        'accepted'                => 'bg-success',
        'rejected'                => 'bg-danger',
        'cancelled'               => 'bg-dark',
        'placement_assigned'      => 'bg-primary',
        'in_training'             => 'bg-info',
        'completed'               => 'bg-success',
        default                   => 'bg-secondary',
    };
}


function status_label(string $status): string
{
    return ucwords(str_replace('_', ' ', $status));
}
function review_stages(): array
{
    return ['secretary', 'field_coordinator', 'hod', 'placement_officer'];
}


function stage_to_role(): array
{
    return [
        'secretary'         => 'secretary',
        'field_coordinator' => 'field_coordinator',
        'hod'               => 'hod',
        'placement_officer' => 'placement_officer',
    ];
}


function stage_to_permission(): array
{
    return [
        'secretary'         => 'review_stage_secretary',
        'field_coordinator' => 'review_stage_field_coordinator',
        'hod'               => 'review_stage_hod',
        'placement_officer' => 'review_stage_placement',
    ];
}


function stage_label(string $stage): string
{
    $labels = [
        'secretary'         => 'Secretary',
        'field_coordinator' => 'Field Coordinator',
        'hod'               => 'HOD',
        'placement_officer' => 'Placement Officer',
        'done'              => 'Completed',
    ];
    return $labels[$stage] ?? ucwords(str_replace('_', ' ', $stage));
}


function stage_badge_class(string $stage): string
{
    return match ($stage) {
        'secretary'         => 'bg-info',
        'field_coordinator' => 'bg-primary',
        'hod'               => 'bg-warning text-dark',
        'placement_officer' => 'bg-success',
        'done'              => 'bg-secondary',
        default             => 'bg-secondary',
    };
}


function next_stage(string $currentStage): string
{
    $stages = review_stages();
    $idx = array_search($currentStage, $stages, true);
    if ($idx === false || $idx === count($stages) - 1) {
        return 'done';
    }
    return $stages[$idx + 1];
}


function can_user_act_on_stage(PDO $pdo, int $userId, string $stage): bool
{
    if ($stage === 'done') return false;
    if (user_has_role($pdo, $userId, 'admin')) return true;
    $map = stage_to_role();
    $requiredRole = $map[$stage] ?? null;
    if (!$requiredRole) return false;
    return user_has_role($pdo, $userId, $requiredRole);
}


function can_user_act_on_stage_by_permission(PDO $pdo, int $userId, string $stage): bool
{
    if ($stage === 'done') return false;
    $map = stage_to_permission();
    $requiredPerm = $map[$stage] ?? null;
    if (!$requiredPerm) return false;
    return user_has_permission($pdo, $userId, $requiredPerm);
}


function get_application_stage_counts(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT current_review_stage, COUNT(*) AS cnt
        FROM applications
        WHERE status NOT IN ('draft', 'rejected', 'cancelled')
        GROUP BY current_review_stage
    ");
    $results = $stmt->fetchAll();
    $counts = [];
    foreach ($results as $row) {
        $counts[$row['current_review_stage']] = (int) $row['cnt'];
    }
    return $counts;
}


function get_user_stages(PDO $pdo, int $userId): array
{
    if (user_has_role($pdo, $userId, 'admin')) {
        return review_stages();
    }
    $stages = [];
    $map = stage_to_role();
    foreach ($map as $stage => $roleName) {
        if (user_has_role($pdo, $userId, $roleName)) {
            $stages[] = $stage;
        }
    }
    return $stages;
}


function count_pending_for_user(PDO $pdo, int $userId): int
{
    $stages = get_user_stages($pdo, $userId);
    if (empty($stages)) return 0;
    $placeholders = implode(',', array_fill(0, count($stages), '?'));
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM applications
        WHERE status IN ('submitted', 'under_review', 'accepted')
          AND current_review_stage IN ($placeholders)
    ");
    $stmt->execute($stages);
    return (int) $stmt->fetchColumn();
}


function get_pending_for_user(PDO $pdo, int $userId): array
{
    $stages = get_user_stages($pdo, $userId);
    if (empty($stages)) return [];
    $placeholders = implode(',', array_fill(0, count($stages), '?'));
    $stmt = $pdo->prepare("
        SELECT a.*, s.full_name AS student_name, s.registration_no,
               tw.name AS window_name, tt.name AS training_type_name
        FROM applications a
        JOIN students s ON a.student_id = s.id
        LEFT JOIN application_windows tw ON a.application_window_id = tw.id
        LEFT JOIN training_types tt ON a.training_type_id = tt.id
        WHERE a.status IN ('submitted', 'under_review', 'accepted')
          AND a.current_review_stage IN ($placeholders)
        ORDER BY a.created_at ASC
    ");
    $stmt->execute($stages);
    return $stmt->fetchAll();
}
