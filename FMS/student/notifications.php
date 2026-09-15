<?php
require_once __DIR__ . '/../includes/student_check.php';

$pageTitle = 'Notifications';

$studentId = (int) $currentStudent['id'];

$stmt = $pdo->prepare("
    UPDATE students
    SET notifications_seen_at = NOW()
    WHERE id = ?
");

$stmt->execute([$studentId]);

$notifications = get_student_notifications($pdo, $studentId);

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
                                        <span class="badge <?= status_badge_class($n['type']) ?> mr-2"><?= status_label($n['type']) ?></span>
                                        <?= e($n['message']) ?>
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
