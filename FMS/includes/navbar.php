<?php

?>
<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>
    </ul>

    <ul class="navbar-nav ml-auto">
    <?php if (!empty($currentStudent)): ?>
        <?php
        $navNotifications = get_student_notifications($pdo, (int) $currentStudent['id']);

        $seenAt = $currentStudent['notifications_seen_at'] ?? null;

        if ($seenAt) {
            $navNotifications = array_filter($navNotifications, function ($notification) use ($seenAt) {
                return $notification['date'] > $seenAt;
            });
        }

        $notificationCount = count($navNotifications);
        ?>

        <li class="nav-item dropdown notification-menu">
            <a class="nav-link notification-toggle" href="#" role="button"
               aria-haspopup="true" aria-expanded="false" aria-label="Notifications">
                <i class="fas fa-bell"></i>

                <?php if ($notificationCount > 0): ?>
                    <span class="notification-count">
                        <?= $notificationCount > 99 ? '99+' : $notificationCount ?>
                    </span>
                <?php endif; ?>
            </a>

            <div class="dropdown-menu dropdown-menu-right notification-dropdown">
                <div class="notification-heading">
                    <strong>Notifications</strong>
                    <span><?= $notificationCount ?></span>
                </div>

                <?php if ($notificationCount === 0): ?>
                    <div class="notification-empty">
                        No notifications yet.
                    </div>
                <?php else: ?>
                    <?php foreach (array_slice($navNotifications, 0, 5) as $notification): ?>
                        <div class="notification-item">
                            <span class="badge <?= status_badge_class($notification['type']) ?>">
                                <?= e(status_label($notification['type'])) ?>
                            </span>

                            <div class="notification-message">
                                <?= e($notification['message']) ?>
                            </div>

                            <small>
                                <?= e(format_datetime($notification['date'])) ?>
                            </small>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <a href="/FMS/student/notifications.php" class="notification-view-all">
                    View all notifications
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </li>

        <?php endif; ?>
        
        <li class="nav-item dropdown user-menu">
            <a class="nav-link dropdown-toggle" href="#" role="button"
               data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <?php if (!empty($currentStudent)): ?>
                    <?php if (!empty($currentStudent['profile_picture']) && file_exists(__DIR__ . '/../uploads/profiles/' . $currentStudent['profile_picture'])): ?>
                        <img class="user-image" src="/FMS/uploads/profiles/<?= e($currentStudent['profile_picture']) ?>" alt="Profile">
                    <?php else: ?>
                        <i class="fas fa-user-circle user-image user-icon"></i>
                    <?php endif; ?>
                    <span class="d-none d-md-inline"><?= e($currentStudent['full_name']) ?></span>
                <?php elseif (!empty($currentUser)): ?>
                    <i class="fas fa-user-circle user-image user-icon"></i>
                    <span class="d-none d-md-inline"><?= e($currentUser['full_name']) ?></span>
                <?php else: ?>
                    <i class="fas fa-user-circle user-image user-icon"></i>
                    <span class="d-none d-md-inline">Guest</span>
                <?php endif; ?>
            </a>
            <div class="dropdown-menu dropdown-menu-right">
                <div class="user-header">
                    <?php if (!empty($currentStudent) && !empty($currentStudent['profile_picture']) && file_exists(__DIR__ . '/../uploads/profiles/' . $currentStudent['profile_picture'])): ?>
                        <img src="/FMS/uploads/profiles/<?= e($currentStudent['profile_picture']) ?>" class="img-circle" alt="Profile">
                    <?php else: ?>
                        <i class="fas fa-user-circle user-header-icon"></i>
                    <?php endif; ?>
                    <p>
                        <?= e($currentStudent['full_name'] ?? $currentUser['full_name'] ?? 'Guest') ?>
                        <small><?= !empty($currentStudent) ? 'Student account' : 'Staff account' ?></small>
                    </p>
                </div>
                <div class="user-footer">
                <?php if (!empty($currentStudent)): ?>
                    <a href="/FMS/student/profile.php" class="user-action user-action-profile">
                        <i class="fas fa-user mr-1"></i> Profile
                    </a>
                <?php elseif (!empty($currentUser) && !current_user_is_admin()): ?>
                    <a href="/FMS/staff/profile.php" class="user-action user-action-profile">
                        <i class="fas fa-user mr-1"></i> Profile
                    </a>
                <?php endif; ?>
                <a href="/FMS/auth/logout.php" class="user-action user-action-signout">
                    <i class="fas fa-sign-out-alt mr-1"></i> Sign out
                </a>
                </div>
            </div>
        </li>
    </ul>
</nav>
