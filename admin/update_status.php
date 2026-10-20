<?php
/**
 * admin/update_status.php — Handle booking status updates & Captain KYC verification
 */

if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['admin_id'])) {
    header('Location: ../login.php?role=admin');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';
$pdo = getDB();

// Handle Captain KYC Verification (SRS Section 5.5)
if (isset($_POST['update_kyc'])) {
    $captain_id = intval($_POST['captain_id'] ?? 0);
    $new_kyc    = trim($_POST['new_kyc'] ?? 'Pending');
    $allowed_kyc = ['Approved', 'Pending', 'Rejected'];

    if ($captain_id > 0 && in_array($new_kyc, $allowed_kyc, true)) {
        $stmt = $pdo->prepare('UPDATE captains SET kyc_status = ? WHERE id = ?');
        $stmt->execute([$new_kyc, $captain_id]);
        $_SESSION['flash_success'] = "Captain #{$captain_id} KYC status updated to: {$new_kyc}";
    } else {
        $_SESSION['flash_error'] = "Invalid Captain KYC request.";
    }
    header('Location: dashboard.php#captains-section');
    exit;
}

// Handle Booking Status Update
$booking_id = trim($_POST['booking_id'] ?? '');
$new_status = trim($_POST['new_status'] ?? '');
$allowed_statuses = ['Pending', 'Accepted', 'In_Progress', 'Completed', 'Cancelled'];

if (empty($booking_id) || !in_array($new_status, $allowed_statuses, true)) {
    $_SESSION['flash_error'] = 'Invalid status update request.';
    header('Location: dashboard.php');
    exit;
}

$stmt = $pdo->prepare('UPDATE bookings SET status = ? WHERE booking_id = ?');
$stmt->execute([$new_status, $booking_id]);

$_SESSION['flash_success'] = "Booking {$booking_id} status changed to {$new_status}.";
header('Location: dashboard.php');
exit;