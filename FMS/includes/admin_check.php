<?php
require_once __DIR__ . '/auth_check.php';
if (!$currentUser) {
    set_flash('error', 'Access denied. Staff login required.');
    redirect('/FMS/auth/login.php');
}
$currentUserRoles = get_user_roles($pdo, (int) $currentUser['id']);
$validStaffRoles = [
    'admin',
    'secretary',
    'field_coordinator',
    'hod',
    'placement_officer',
    'academic_supervisor',
    'industrial_supervisor',
    'supervisor',
];
$hasStaffRole = false;
foreach ($currentUserRoles as $role) {
    if (in_array($role, $validStaffRoles, true)) {
        $hasStaffRole = true;
        break;
    }
}
if (!$hasStaffRole) {
    set_flash('error', 'Access denied. You do not have permission to view that page.');
    redirect('/FMS/index.php');
}
function current_user_has_role(string $roleName): bool
{
    global $currentUserRoles;
    return in_array($roleName, $currentUserRoles, true);
}
function current_user_is_admin(): bool
{
    return current_user_has_role('admin');
}
