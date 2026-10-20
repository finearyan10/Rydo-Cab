<?php
/**
 * admin/login.php — Admin login entry point.
 *
 * The standalone admin login form has been retired in favour of the single
 * unified login page at the project root (login.php), which already
 * authenticates against the same `admins` table and sets the same
 * $_SESSION['admin_id'] / $_SESSION['admin_username'] keys this app's
 * admin pages (dashboard.php, update_status.php, logout.php) expect.
 *
 * Keeping this file (rather than deleting it) means old bookmarks/links
 * to admin/login.php still work — it just hands off to the real login page,
 * with the Admin tab preselected.
 */

if (session_status() === PHP_SESSION_NONE) session_start();

if (!empty($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

header('Location: ../login.php?role=admin');
exit;