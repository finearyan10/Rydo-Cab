<?php
/**
 * captain/dashboard.php — RYDO Captain (Driver) Partner Portal
 * SRS Section 1.4 & 3.1: Online/Offline toggle, OTP verification, trip execution & earnings
 */

if (session_status() === PHP_SESSION_NONE) session_start();

// Require a logged-in captain — previously this page trusted ?cid= from the
// URL with no login at all, so it always fell back to Captain #1 (Vikram
// Patil) and anyone could view or act on any captain's trips by editing
// the URL.
if (empty($_SESSION['captain_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';
$pdo = getDB();

$captain_id = (int)$_SESSION['captain_id'];

// Handle Online / Offline toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_online'])) {
    $new_status = intval($_POST['is_online'] ?? 0);
    $upd = $pdo->prepare('UPDATE captains SET is_online = ? WHERE id = ?');
    $upd->execute([$new_status, $captain_id]);
    $_SESSION['cap_flash'] = "Status updated to: " . ($new_status ? "🟢 Online & Receiving Trips" : "⚪ Offline");
    header("Location: dashboard.php");
    exit;
}

// Handle OTP Verification & Start Trip (SRS Section 4.2)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_otp_start'])) {
    $booking_id = trim($_POST['booking_id'] ?? '');
    $input_otp  = trim($_POST['otp_pin'] ?? '');

    $chk = $pdo->prepare('SELECT id, otp_pin, status FROM bookings WHERE booking_id = ?');
    $chk->execute([$booking_id]);
    $booking = $chk->fetch();

    if ($booking && $booking['otp_pin'] === $input_otp) {
        $st = $pdo->prepare("UPDATE bookings SET status = 'In_Progress', captain_id = ? WHERE booking_id = ?");
        $st->execute([$captain_id, $booking_id]);
        $_SESSION['cap_flash'] = "✅ PIN Verified! Trip started successfully.";
    } else {
        $_SESSION['cap_error'] = "❌ Invalid 4-Digit PIN. Please ask the passenger for the correct code.";
    }
    header("Location: dashboard.php");
    exit;
}

// Handle Trip Completion (SRS Section 4.4 & 5.5)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_trip'])) {
    $booking_id = trim($_POST['booking_id'] ?? '');
    
    // Fetch booking details for earnings calculation
    $b_stmt = $pdo->prepare('SELECT captain_earning FROM bookings WHERE booking_id = ?');
    $b_stmt->execute([$booking_id]);
    $b_data = $b_stmt->fetch();
    $earning = $b_data ? floatval($b_data['captain_earning']) : 0.00;

    $st = $pdo->prepare("UPDATE bookings SET status = 'Completed', captain_id = ? WHERE booking_id = ?");
    $st->execute([$captain_id, $booking_id]);

    // Update Captain cumulative earnings
    $upd_earn = $pdo->prepare("UPDATE captains SET total_earnings = total_earnings + ? WHERE id = ?");
    $upd_earn->execute([$earning, $captain_id]);

    $_SESSION['cap_flash'] = "🏁 Trip completed! ₹{$earning} credited to your Captain wallet (80% net payout).";
    header("Location: dashboard.php");
    exit;
}

// Fetch Captain profile
$cap_stmt = $pdo->prepare('SELECT * FROM captains WHERE id = ?');
$cap_stmt->execute([$captain_id]);
$captain = $cap_stmt->fetch();

if (!$captain) {
    // The session points at a captain row that no longer exists — don't
    // silently fall back to a different captain's dashboard, log out instead.
    session_destroy();
    header('Location: login.php');
    exit;
}

// Fetch active & pending bookings for this captain or general pool
$active_stmt = $pdo->prepare(
    "SELECT * FROM bookings
     WHERE (captain_id = :cid OR (captain_id IS NULL AND status IN ('Pending', 'Accepted')))
       AND status IN ('Pending', 'Accepted', 'In_Progress')
     ORDER BY booked_at DESC"
);
$active_stmt->execute([':cid' => $captain_id]);
$active_trips = $active_stmt->fetchAll();

// Fetch completed trips for this captain
$comp_stmt = $pdo->prepare(
    "SELECT * FROM bookings WHERE captain_id = ? AND status = 'Completed' ORDER BY updated_at DESC LIMIT 5"
);
$comp_stmt->execute([$captain_id]);
$completed_trips = $comp_stmt->fetchAll();

$flash = $_SESSION['cap_flash'] ?? '';
$error = $_SESSION['cap_error'] ?? '';
unset($_SESSION['cap_flash'], $_SESSION['cap_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Captain Partner Dashboard — RYDO</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-body">

<nav class="admin-navbar">
    <div class="container">
        <div class="admin-navbar-brand">
            <span class="brand-icon">C</span>
            <span class="admin-navbar-title">RYDO Captain Partner</span>
        </div>
        <div class="admin-navbar-actions">
            <span class="admin-user">Hi, <?= htmlspecialchars($captain['name']) ?></span>
            <a href="../index.php" class="btn btn-secondary btn-sm">Rider App</a>
            <a href="../login.php?role=admin" class="btn btn-secondary btn-sm">Admin Panel</a>
            <a href="logout.php" class="btn btn-secondary btn-sm">Log Out</a>
        </div>
    </div>
</nav>

<div class="admin-page">
    <div class="container">

        <?php if ($flash): ?>
            <div class="alert alert-success"><?= htmlspecialchars($flash) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card profile-card">
            <div class="profile-main">
                <div class="avatar avatar-lg">
                    <?= strtoupper(substr($captain['name'], 0, 1)) ?>
                </div>
                <div>
                    <h2><?= htmlspecialchars($captain['name']) ?></h2>
                    <p>
                        Vehicle: <strong><?= htmlspecialchars($captain['vehicle_number']) ?></strong> (<?= htmlspecialchars($captain['vehicle_type']) ?>)
                        • Lic: <code><?= htmlspecialchars($captain['license_number']) ?></code>
                    </p>
                    <div class="profile-badges">
                        <span class="badge badge-<?= ($captain['kyc_status'] === 'Approved') ? 'completed' : 'pending' ?>">
                            KYC: <?= htmlspecialchars($captain['kyc_status']) ?>
                        </span>
                        <span class="rating-text"><?= htmlspecialchars($captain['rating']) ?> rating</span>
                    </div>
                </div>
            </div>

            <form method="POST">
                <input type="hidden" name="toggle_online" value="1">
                <input type="hidden" name="is_online" value="<?= $captain['is_online'] ? '0' : '1' ?>">
                <button type="submit" class="btn <?= $captain['is_online'] ? 'btn-success' : 'btn-secondary' ?>">
                    <?= $captain['is_online'] ? 'On duty (Online)' : 'Off duty (Offline)' ?>
                </button>
            </form>
        </div>

        <div class="admin-stats">
            <div class="stat-card">
                <div class="stat-icon">Rs</div>
                <div>
                    <div class="stat-value">₹<?= number_format((float)$captain['total_earnings'], 2) ?></div>
                    <div class="stat-label">Total earnings (80% net)</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">Done</div>
                <div>
                    <div class="stat-value"><?= count($completed_trips) ?></div>
                    <div class="stat-label">Recent completed trips</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">Live</div>
                <div>
                    <div class="stat-value"><?= count($active_trips) ?></div>
                    <div class="stat-label">Active / incoming requests</div>
                </div>
            </div>
        </div>

        <h2 class="panel-heading">Active trips &amp; assigned requests</h2>

        <?php if (empty($active_trips)): ?>
            <div class="card empty-state">
                <div class="empty-icon">—</div>
                <p>No active ride requests at this moment. Stay online to receive incoming trips.</p>
            </div>
        <?php else: ?>
            <div class="trip-grid">
                <?php foreach ($active_trips as $t): ?>
                    <div class="card trip-card">
                        <div class="trip-card-top">
                            <span class="trip-service">
                                <?= ($t['service_type'] === 'parcel') ? 'Parcel' : htmlspecialchars($t['vehicle_type']) ?>
                            </span>
                            <span class="badge badge-<?= strtolower($t['status']) ?>">
                                <?= htmlspecialchars($t['status']) ?>
                            </span>
                        </div>

                        <h3><?= htmlspecialchars($t['passenger_name']) ?></h3>
                        <p class="trip-meta">
                            <?= htmlspecialchars($t['phone']) ?> · <?= htmlspecialchars($t['distance_km']) ?> km
                        </p>

                        <div class="route-box">
                            <div><strong>Pickup:</strong> <?= htmlspecialchars($t['pickup']) ?></div>
                            <div><strong>Drop:</strong> <?= htmlspecialchars($t['destination']) ?></div>
                            <?php if ($t['service_type'] === 'parcel'): ?>
                                <div>
                                    <strong>Recipient:</strong> <?= htmlspecialchars($t['receiver_name']) ?> (<?= htmlspecialchars($t['receiver_phone']) ?>)
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="payout-row">
                            <span>Captain net payout (80%)</span>
                            <strong>₹<?= number_format((float)$t['captain_earning'], 2) ?></strong>
                        </div>

                        <?php if ($t['status'] === 'Accepted' || $t['status'] === 'Pending'): ?>
                            <form method="POST" class="otp-form">
                                <input type="hidden" name="verify_otp_start" value="1">
                                <input type="hidden" name="booking_id" value="<?= htmlspecialchars($t['booking_id']) ?>">
                                <label>Enter passenger 4-digit PIN to start</label>
                                <div class="otp-form-row">
                                    <input type="text" name="otp_pin" class="form-control otp-input" placeholder="PIN" maxlength="4" required>
                                    <button type="submit" class="btn btn-primary btn-sm">Start trip</button>
                                </div>
                            </form>
                        <?php elseif ($t['status'] === 'In_Progress'): ?>
                            <form method="POST">
                                <input type="hidden" name="complete_trip" value="1">
                                <input type="hidden" name="booking_id" value="<?= htmlspecialchars($t['booking_id']) ?>">
                                <button type="submit" class="btn btn-success btn-block">
                                    Complete trip / confirm delivery
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <h2 class="panel-heading">Recent trip history</h2>
        <div class="card card--flush">
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Service</th>
                            <th>Passenger</th>
                            <th>Route</th>
                            <th>Payout</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($completed_trips)): ?>
                            <tr>
                                <td colspan="6" class="empty-row">No completed trips yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($completed_trips as $h): ?>
                            <tr>
                                <td>
                                    <a class="booking-id" href="../confirmation.php?bid=<?= urlencode($h['booking_id']) ?>">
                                        <?= htmlspecialchars($h['booking_id']) ?>
                                    </a>
                                </td>
                                <td><?= ($h['service_type'] === 'parcel') ? 'Parcel' : htmlspecialchars($h['vehicle_type']) ?></td>
                                <td><?= htmlspecialchars($h['passenger_name']) ?></td>
                                <td><?= htmlspecialchars($h['pickup']) ?> → <?= htmlspecialchars($h['destination']) ?></td>
                                <td>₹<?= number_format((float)$h['captain_earning'], 2) ?></td>
                                <td>
                                    <span class="badge badge-<?= strtolower($h['status']) ?>">
                                        <?= htmlspecialchars($h['status']) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>
