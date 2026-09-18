<?php

require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['user_id'])) {
    redirect('/FMS/auth/login.php');
}

if (!user_has_role($pdo, (int) $_SESSION['user_id'], 'admin')) {
    set_flash('error', 'Access denied. Only administrators can perform this action.');
    redirect('/FMS/index.php');
}

if (!verify_csrf()) {
    set_flash('error', 'Invalid form submission.');
    redirect('/FMS/admin/dashboard.php');
}

$entity = $_POST['entity'] ?? '';
$id = (int) ($_POST['id'] ?? 0);
$targets = [
    'application_window' => ['table' => 'application_windows', 'page' => '/FMS/admin/application_windows.php', 'label' => 'Application window'],
    'department' => ['table' => 'departments', 'page' => '/FMS/admin/departments.php', 'label' => 'Department'],
    'institution' => ['table' => 'institutions', 'page' => '/FMS/admin/institutions.php', 'label' => 'Institution'],
    'specialization' => ['table' => 'specializations', 'page' => '/FMS/admin/specializations.php', 'label' => 'Specialization'],
    'student' => ['table' => 'students', 'page' => '/FMS/admin/students.php', 'label' => 'Student'],
    'supervisor' => ['table' => 'users', 'page' => '/FMS/admin/supervisors.php', 'label' => 'Staff account'],
];

if ($id < 1 || !isset($targets[$entity])) {
    set_flash('error', 'Invalid record.');
    redirect('/FMS/admin/dashboard.php');
}

$target = $targets[$entity];
$supervisorCheck = '';
if ($entity === 'supervisor') {
    $supervisorCheck = " AND EXISTS (
        SELECT 1 FROM user_roles ur
        JOIN roles r ON r.id = ur.role_id
        WHERE ur.user_id = users.id
          AND r.name IN ('secretary', 'field_coordinator', 'hod', 'placement_officer', 'academic_supervisor', 'industrial_supervisor', 'supervisor')
    )";
}
$stmt = $pdo->prepare("SELECT deleted FROM {$target['table']} WHERE id = ?{$supervisorCheck}");
$stmt->execute([$id]);
$record = $stmt->fetch();
if (!$record) {
    set_flash('error', $target['label'] . ' not found.');
    redirect($target['page']);
}

$deleted = (int) $record['deleted'] === 1 ? 0 : 1;
$stmt = $pdo->prepare("UPDATE {$target['table']} SET deleted = ? WHERE id = ?");
$stmt->execute([$deleted, $id]);

set_flash('success', $target['label'] . ($deleted ? ' deleted.' : ' restored.'));
redirect($target['page']);