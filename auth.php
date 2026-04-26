<?php
// ================================================================
//  LTI GRADING SYSTEM — auth.php
//  Include at the TOP of every protected page.
//  Sets session + exposes role/permission helpers.
// ================================================================

if (session_status() === PHP_SESSION_NONE) session_start();

// ── Reject unauthenticated visitors ────────────────────────────
if (empty($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// ── Role helper functions ───────────────────────────────────────

function current_role(): string {
    return $_SESSION['role'] ?? '';
}
function is_superadmin(): bool { return current_role() === 'superadmin'; }
function is_faculty(): bool    { return current_role() === 'faculty'; }
function is_student(): bool    { return current_role() === 'student'; }

// Superadmin only
function can_delete(): bool { return is_superadmin(); }

// Superadmin + Faculty
function can_edit(): bool   { return is_superadmin() || is_faculty(); }
function can_add(): bool    { return is_superadmin() || is_faculty(); }

// ── Page-level role guard ───────────────────────────────────────
// Usage:  require_role('superadmin');
//         require_role(['superadmin','faculty']);
function require_role($roles): void {
    $roles = (array)$roles;
    if (!in_array(current_role(), $roles, true)) {
        $_SESSION['error_msg'] = 'Access denied. You do not have permission.';
        if (is_student()) {
            $sid = intval($_SESSION['student_ref_id'] ?? 0);
            header("Location: student_record.php?id=$sid");
        } else {
            header('Location: index.php');
        }
        exit();
    }
}

// Student may only view their own record
function require_owns_student(int $requested_id): void {
    if (is_student() && $requested_id !== intval($_SESSION['student_ref_id'] ?? 0)) {
        $_SESSION['error_msg'] = 'You may only view your own record.';
        $sid = intval($_SESSION['student_ref_id'] ?? 0);
        header("Location: student_record.php?id=$sid");
        exit();
    }
}
