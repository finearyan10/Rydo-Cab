<?php
/**
 * captain/logout.php — Ends the captain's session.
 */

if (session_status() === PHP_SESSION_NONE) session_start();

unset($_SESSION['captain_id'], $_SESSION['captain_name'], $_SESSION['captain_csrf']);

header('Location: login.php');
exit;