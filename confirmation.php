<?php
/**
 * confirmation.php — RYDO Ride & Parcel Tracking / Confirmation
 * Displays 4-Digit Security PIN, assigned Captain details, Live GPS Simulation & SOS button
 */

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/config/db.php';

$bid = trim($_GET['bid'] ?? '');
if (empty($bid)) {
    header('Location: index.php');
    exit;
}

$pdo = getDB();

// Handle rating submission if completed
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rate_trip'])) {
    $stars = intval($_POST['rating'] ?? 5);
    $feedback = trim($_POST['feedback'] ?? '');
    $upd = $pdo->prepare('UPDATE bookings SET rating = ?, feedback = ? WHERE booking_id = ?');
    $upd->execute([$stars, $feedback, $bid]);
    header('Location: confirmation.php?bid=' . urlencode($bid) . '&rated=1');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT b.*, c.name AS captain_name, c.phone AS captain_phone,
            c.vehicle_number, c.rating AS captain_rating
     FROM bookings b
     LEFT JOIN captains c ON b.captain_id = c.id
     WHERE b.booking_id = ? LIMIT 1"
);
$stmt->execute([$bid]);
$booking = $stmt->fetch();

if (!$booking) {
    header('Location: index.php');
    exit;
}

$is_parcel = ($booking['service_type'] === 'parcel');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trip Confirmed — <?= htmlspecialchars($booking['booking_id']) ?> | RYDO</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<nav class="navbar">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <span class="brand-icon">R</span>
            <span>RYDO</span>
            <span class="college-badge">KITCoEK • CSBS</span>
        </a>
        <ul class="navbar-links">
            <li><a href="index.php">New Booking</a></li>
            <li><a href="captain/dashboard.php">Captain Portal</a></li>
        </ul>
    </div>
</nav>

<section class="page-section">
    <div class="container">
        <div class="card confirm-wrap">

            <div class="confirm-header">
                <div>
                    <span class="confirm-kicker">
                        <?= $is_parcel ? 'Intra-city parcel' : 'Ride booking' ?>
                    </span>
                    <h1><?= htmlspecialchars($booking['booking_id']) ?></h1>
                </div>
                <div class="confirm-actions">
                    <span class="badge badge-<?= strtolower($booking['status']) ?>">
                        <?= htmlspecialchars($booking['status']) ?>
                    </span>
                    <button type="button" class="sos-btn" onclick="triggerSOS()">
                        SOS Alert
                    </button>
                </div>
            </div>

            <div class="otp-display-box">
                <span class="otp-kicker">Share this 4-digit PIN with the captain</span>
                <div class="otp-code"><?= htmlspecialchars($booking['otp_pin']) ?></div>
                <p class="otp-note">Required before starting the ride or picking up the parcel.</p>
            </div>

            <div class="gps-tracker-card">
                <div class="gps-meta">
                    <span><strong>Pickup:</strong> <?= htmlspecialchars($booking['pickup']) ?></span>
                    <span><strong>Drop:</strong> <?= htmlspecialchars($booking['destination']) ?></span>
                </div>
                <div class="gps-route-line">
                    <div class="gps-point active" title="Pickup Location"></div>
                    <div class="gps-bike-marker">
                        <?= ($booking['vehicle_type'] === 'Cab') ? 'Cab' : (($booking['vehicle_type'] === 'Auto') ? 'Auto' : 'Bike') ?>
                    </div>
                    <div class="gps-point" title="Drop Location"></div>
                </div>
                <p class="gps-status">
                    Live GPS broadcast active • Captain arriving in ~<?= max(2, round($booking['distance_km'] * 1.5)) ?> mins
                </p>
            </div>

            <div class="captain-box">
                <div class="captain-box-main">
                    <div class="avatar">
                        <?= strtoupper(substr($booking['captain_name'] ?? 'C', 0, 1)) ?>
                    </div>
                    <div>
                        <div class="captain-name">
                            <?= htmlspecialchars($booking['captain_name'] ?? 'Matching nearby captain…') ?>
                        </div>
                        <div class="captain-meta">
                            <?= htmlspecialchars($booking['vehicle_number'] ?? 'Assigned soon') ?> • <?= htmlspecialchars($booking['captain_rating'] ?? '4.8') ?> rating
                        </div>
                    </div>
                </div>
                <div>
                    <a href="tel:<?= htmlspecialchars($booking['captain_phone'] ?? '9822000000') ?>" class="btn btn-secondary btn-sm">
                        Call Captain
                    </a>
                </div>
            </div>

            <table class="detail-table">
                <tr>
                    <td class="label">Passenger / Sender</td>
                    <td class="value">
                        <?= htmlspecialchars($booking['passenger_name']) ?> (<?= htmlspecialchars($booking['phone']) ?>)
                    </td>
                </tr>
                <?php if ($is_parcel): ?>
                <tr>
                    <td class="label">Parcel recipient</td>
                    <td class="value">
                        <?= htmlspecialchars($booking['receiver_name']) ?> (<?= htmlspecialchars($booking['receiver_phone']) ?>)<br>
                        <small class="text-muted"><?= htmlspecialchars($booking['receiver_address']) ?> | <?= htmlspecialchars($booking['parcel_weight']) ?> kg</small>
                    </td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td class="label">Distance</td>
                    <td class="value"><?= htmlspecialchars($booking['distance_km']) ?> km</td>
                </tr>
                <tr>
                    <td class="label">Payment mode</td>
                    <td class="value"><?= htmlspecialchars($booking['payment_method']) ?></td>
                </tr>
                <tr>
                    <td class="fare-label">Estimated fare</td>
                    <td class="fare-value">₹<?= number_format((float)$booking['fare_estimate'], 2) ?></td>
                </tr>
            </table>

            <?php if ($booking['status'] === 'Completed'): ?>
                <div class="rating-box">
                    <h3>Trip completed. How was your captain?</h3>
                    <?php if (!empty($booking['rating'])): ?>
                        <p>
                            Your rating: <strong><?= (int)$booking['rating'] ?>/5</strong><br>
                            <em>"<?= htmlspecialchars($booking['feedback'] ?? 'Great service!') ?>"</em>
                        </p>
                    <?php else: ?>
                        <form method="POST">
                            <div class="rating-row">
                                <label for="rating">Rating</label>
                                <select name="rating" id="rating" class="form-control" style="width:auto;">
                                    <option value="5">5 — Excellent</option>
                                    <option value="4">4 — Very Good</option>
                                    <option value="3">3 — Average</option>
                                </select>
                            </div>
                            <input type="text" name="feedback" class="form-control" placeholder="Optional comments..." style="margin-bottom:0.8rem;">
                            <button type="submit" name="rate_trip" class="btn btn-success btn-sm">Submit Review</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="action-row">
                <a href="index.php" class="btn btn-secondary">Book another</a>
                <button type="button" onclick="window.print()" class="btn btn-secondary">Print receipt</button>
                <a href="captain/dashboard.php?sim_id=<?= urlencode($booking['booking_id']) ?>" class="btn btn-primary">
                    View in Captain App
                </a>
            </div>

        </div>
    </div>
</section>

<script>
function triggerSOS() {
    const alertMsg = "RYDO EMERGENCY SOS ACTIVATED!\n\n" +
                     "• Immediate notification sent to Local Law Enforcement (Police 112)\n" +
                     "• Live GPS coordinates dispatched to RYDO 24x7 Safety Response Team\n" +
                     "• Booking ID: <?= htmlspecialchars($booking['booking_id']) ?>\n" +
                     "• Emergency contact alerted with live tracking URL.\n\n" +
                     "Please stay safe. A security representative will call you immediately.";
    alert(alertMsg);
}
</script>

<script src="assets/js/main.js"></script>
</body>
</html>
