<?php
/**
 * Staff Notifications
 *
 * Shows the staff user notifications about applications that are
 * currently at their review stage and need action, plus recent
 * comments on applications they have reviewed.
 */
require_once __DIR__ . '/../includes/admin_check.php';

// Admins use the admin dashboard
if (current_user_is_admin()) {
    redirect('/FMS/admin/dashboard.php');
}

$pageTitle = 'Notifications';

$userId = (int) $currentUser['id'];

// Get applications pending at this user's stage(s)
$pendingApps = get_pending_for_user($pdo, $userId);

// Get the user's stages
$userStages = get_user_stages($pdo, $userId);

// Build notifications list
$notifications = [];

foreach ($pendingApps as $pa) {
    $stageLabel = stage_label($pa['current_review_stage'] ?? 'secretary');
    $notifications[] = [
        'type'    => 'pending',
        'message' => 'Application ' . $pa['reference_number'] . ' from ' . $pa['student_name'] . ' (' . $pa['registration_no'] . ') is at the ' . $stageLabel . ' stage and needs your action.',
        'date'    => $pa['submission_date'] ?? $pa['created_at'],
        'link'   => '/FMS/admin/view_application.php?id=' . (int) $pa['id'],
    ];
}

// Get recent comments on applications this user has reviewed
$stmt = $pdo->prepare("
    SELECT c.*, a.reference_number, u.full_name AS commenter_name
    FROM comments c
    JOIN applications a ON c.commentable_type = 'application' AND c.commentable_id = a.id
    LEFT JOIN users u ON c.user_id = u.id
    WHERE c.user_id != ? AND c.commentable_type = 'application'
    ORDER BY c.created_at DESC
    LIMIT 10
");
$stmt->execute([$userId]);
$recentComments = $stmt->fetchAll();

foreach ($recentComments as $c) {
    $notifications[] = [
        'type'    => 'comment',
        'message' => 'New comment by ' . ($c['commenter_name'] ?? 'Unknown') . ' on application ' . $c['reference_number'] . ': "' . $c['comment_text'] . '"',
        'date'    => $c['created_at'],
        'link'   => '/FMS/admin/applications.php',
    ];
}

// Sort by date, newest first
usort($notifications, fn($a, $b) => strtotime($b['date']) <=> strtotime($a['date']));

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1 class="m-0">Notifications</h1>
        </div>
    </div>
    <div class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Recent Notifications</h3></div>
                <div class="card-body">
                    <?php if (empty($notifications)): ?>
                        <p class="text-muted">You have no notifications at this time.</p>
                    <?php else: ?>
                        <ul class="list-group">
                            <?php foreach ($notifications as $n): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <?php if ($n['type'] === 'pending'): ?>
                                            <span class="badge bg-warning text-dark mr-2">Action Needed</span>
                                        <?php else: ?>
                                            <span class="badge bg-info mr-2">Comment</span>
                                        <?php endif; ?>
                                        <a href="<?= e($n['link']) ?>"><?= e($n['message']) ?></a>
                                    </div>
                                    <small class="text-muted"><?= format_datetime($n['date']) ?></small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
