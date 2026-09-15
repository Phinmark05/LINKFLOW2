<?php
?>
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="/FMS/index.php" class="brand-link text-center">
        <img src="/FMS/assets/img/Screenshot 2026-09-08 at 09-25-17 Design Editor.png" alt="LinkFlow Logo" class="brand-image img-circle elevation-3" style="opacity: .8; width: 40px; height: 40px;">
        <span class="brand-text font-weight-bold" style="color: white; margin-right: 30px;">LinkFlow</span>
    </a>

    <div class="sidebar">
        <nav class="mt-3">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">

            <?php if (!empty($currentStudent)): ?>
                <!-- Student menu -->
                <li class="nav-item">
                    <a href="/FMS/student/dashboard.php" class="nav-link <?= ($_SERVER['SCRIPT_NAME'] ?? '') === '/FMS/student/dashboard.php' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-tachometer-alt"></i><p>Dashboard</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/FMS/student/profile.php" class="nav-link <?= ($_SERVER['SCRIPT_NAME'] ?? '') === '/FMS/student/profile.php' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-user"></i><p>My Profile</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/FMS/student/application.php" class="nav-link <?= ($_SERVER['SCRIPT_NAME'] ?? '') === '/FMS/student/application.php' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-file-alt"></i><p>New Application</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/FMS/student/my_application.php" class="nav-link <?= ($_SERVER['SCRIPT_NAME'] ?? '') === '/FMS/student/my_application.php' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-clipboard-list"></i><p>My Applications</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/FMS/student/placement.php" class="nav-link <?= ($_SERVER['SCRIPT_NAME'] ?? '') === '/FMS/student/placement.php' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-briefcase"></i><p>My Placement</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/FMS/student/notifications.php" class="nav-link <?= ($_SERVER['SCRIPT_NAME'] ?? '') === '/FMS/student/notifications.php' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-bell"></i><p>Notifications</p>
                    </a>
                </li>

            <?php elseif (!empty($currentUser)): ?>
                <!-- Staff menu -->
                <?php
                $isAdmin = current_user_is_admin();
                $isReviewer = current_user_has_role('secretary')
                           || current_user_has_role('field_coordinator')
                           || current_user_has_role('hod')
                           || current_user_has_role('placement_officer');
                $canManage = $isAdmin;
                ?>

                <?php if ($isAdmin): ?>
                    <!-- ===== ADMIN MENU ===== -->
                    <li class="nav-item">
                        <a href="/FMS/admin/dashboard.php" class="nav-link <?= ($_SERVER['SCRIPT_NAME'] ?? '') === '/FMS/admin/dashboard.php' ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-tachometer-alt"></i><p>Dashboard</p>
                        </a>
                    </li>
                    <?php
                    $pendingCount = (int) $pdo->query("SELECT COUNT(*) FROM applications WHERE status IN ('submitted','under_review','accepted') AND current_review_stage != 'done'")->fetchColumn();
                    ?>
                    <li class="nav-item">
                        <a href="/FMS/admin/applications.php" class="nav-link <?= ($_SERVER['SCRIPT_NAME'] ?? '') === '/FMS/admin/applications.php' ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-file-alt"></i><p>Applications<?php if ($pendingCount > 0): ?> <span class="badge badge-warning right"><?= $pendingCount ?></span><?php endif; ?></p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/FMS/admin/students.php" class="nav-link <?= ($_SERVER['SCRIPT_NAME'] ?? '') === '/FMS/admin/students.php' ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-user-graduate"></i><p>Students</p>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="/FMS/admin/departments.php" class="nav-link <?= ($_SERVER['SCRIPT_NAME'] ?? '') === '/FMS/admin/departments.php' ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-sitemap"></i><p>Departments</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/FMS/admin/supervisors.php" class="nav-link <?= ($_SERVER['SCRIPT_NAME'] ?? '') === '/FMS/admin/supervisors.php' ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-user-tie"></i><p>Supervisors</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/FMS/admin/application_windows.php" class="nav-link <?= ($_SERVER['SCRIPT_NAME'] ?? '') === '/FMS/admin/application_windows.php' ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-calendar-alt"></i><p>App Windows</p>
                        </a>
                    </li>

                <?php else: ?>
                    <!-- ===== NON-ADMIN STAFF MENU ===== -->
                    <li class="nav-item">
                        <a href="/FMS/staff/dashboard.php" class="nav-link <?= ($_SERVER['SCRIPT_NAME'] ?? '') === '/FMS/staff/dashboard.php' ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-tachometer-alt"></i><p>Dashboard</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/FMS/staff/profile.php" class="nav-link <?= ($_SERVER['SCRIPT_NAME'] ?? '') === '/FMS/staff/profile.php' ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-user"></i><p>My Profile</p>
                        </a>
                    </li>
                    <?php if ($isReviewer): ?>
                    <?php
                    $pendingCount = count_pending_for_user($pdo, (int) $currentUser['id']);
                    ?>
                    <li class="nav-item">
                        <a href="/FMS/admin/applications.php" class="nav-link <?= ($_SERVER['SCRIPT_NAME'] ?? '') === '/FMS/admin/applications.php' ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-file-alt"></i><p>Applications<?php if ($pendingCount > 0): ?> <span class="badge badge-warning right"><?= $pendingCount ?></span><?php endif; ?></p>
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a href="/FMS/staff/notifications.php" class="nav-link <?= ($_SERVER['SCRIPT_NAME'] ?? '') === '/FMS/staff/notifications.php' ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-bell"></i><p>Notifications<?php if ($pendingCount > 0): ?> <span class="badge badge-warning right"><?= $pendingCount ?></span><?php endif; ?></p>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endif; ?>

            </ul>
        </nav>
    </div>
</aside>
