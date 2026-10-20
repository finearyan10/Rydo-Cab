<?php
/**
 * index.php — RYDO Rider & Logistics Booking Portal
 * Mini-Project-I (UCBIL0371) | Dept. of CSBS, KITCoEK
 */

if (session_status() === PHP_SESSION_NONE) session_start();

// Require a logged-in customer before showing the booking page.
// Without this, index.php was reachable directly and nothing ever
// sent a first-time visitor to login.php.
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/config/db.php';
$fares = require __DIR__ . '/config/fares.php';

$locations = [
    'KIT College of Engg',
    'Central Bus Stand',
    'Railway Station',
    'Mahalaxmi Temple',
    'Rankala Lake',
    'DYP City Mall',
    'Shivaji University',
    'CPR Hospital',
    'Shiroli MIDC',
    'Airport'
];

$error   = $_SESSION['booking_error']   ?? '';
$success = $_SESSION['booking_success'] ?? '';
unset($_SESSION['booking_error'], $_SESSION['booking_success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RYDO — Book a Ride or Parcel</title>
    <meta name="description" content="RYDO: Safe, convenient, and affordable Bike Taxi and intra-city parcel logistics platform. Developed for KITCoEK CSBS Mini-Project-I.">
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
            <li><a href="index.php" class="active">Book Ride / Parcel</a></li>
            <li><a href="captain/login.php">Captain Portal</a></li>
            <li><a href="login.php?role=admin">Admin Panel</a></li>
            <li>Hi, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Rider') ?></li>
            <li><a href="logout.php">Log Out</a></li>
        </ul>
    </div>
</nav>

<section class="hero">
    <div class="container">
        <div class="hero-tag">Bike taxi, cab &amp; parcel</div>
        <h1>Book a ride or send a parcel <span>with RYDO</span></h1>
        <p>Choose pickup and drop, estimate fare, and confirm. Every trip uses a 4-digit PIN before it starts.</p>

        <div class="service-tabs-wrapper">
            <div class="service-tabs">
                <button type="button" class="service-tab active" id="tab-ride" onclick="switchService('ride')">
                    Bike Taxi &amp; Cabs
                </button>
                <button type="button" class="service-tab" id="tab-parcel" onclick="switchService('parcel')">
                    Parcel (≤ 5kg)
                </button>
            </div>
        </div>
    </div>
</section>

<section class="page-section">
    <div class="container">
        <div class="booking-grid">

            <div class="card">
                <h2 class="section-title" id="form-heading">Request a Ride</h2>
                <p class="section-subtitle">Select locations and a vehicle. Fare is estimated before you confirm.</p>

                <?php if ($error): ?>
                    <div class="alert alert-error" role="alert">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form id="booking-form" action="book.php" method="POST" novalidate>
                    <input type="hidden" name="service_type" id="service_type" value="ride">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="passenger_name">Your Name</label>
                            <input type="text" id="passenger_name" name="passenger_name"
                                   class="form-control" placeholder="e.g. Aryan Chougule"
                                   value="<?= htmlspecialchars($_SESSION['user_name'] ?? '') ?>"
                                   maxlength="100" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Mobile Number</label>
                            <input type="tel" id="phone" name="phone"
                                   class="form-control" placeholder="10-digit mobile number"
                                   maxlength="10" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="pickup">Pickup Location</label>
                            <select id="pickup" name="pickup" class="form-control" required>
                                <option value="">— Select Pickup Point —</option>
                                <?php foreach ($locations as $loc): ?>
                                    <option value="<?= htmlspecialchars($loc) ?>"><?= htmlspecialchars($loc) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="destination">Drop Location</label>
                            <select id="destination" name="destination" class="form-control" required>
                                <option value="">— Select Destination —</option>
                                <?php foreach ($locations as $loc): ?>
                                    <option value="<?= htmlspecialchars($loc) ?>"><?= htmlspecialchars($loc) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div id="ride-vehicle-options">
                        <label>Select Ride Option</label>
                        <div class="vehicle-selector">
                            <label class="vehicle-radio">
                                <input type="radio" name="vehicle_type" value="Bike" checked>
                                <div class="vehicle-card">
                                    <div class="vehicle-icon">Bike</div>
                                    <div class="vehicle-name">RYDO Bike</div>
                                    <div class="vehicle-rate">₹20 base + ₹8/km</div>
                                    <div class="vehicle-desc">Fastest option</div>
                                </div>
                            </label>
                            <label class="vehicle-radio">
                                <input type="radio" name="vehicle_type" value="Auto">
                                <div class="vehicle-card">
                                    <div class="vehicle-icon">Auto</div>
                                    <div class="vehicle-name">RYDO Auto</div>
                                    <div class="vehicle-rate">₹30 base + ₹12/km</div>
                                    <div class="vehicle-desc">Budget 3-seater</div>
                                </div>
                            </label>
                            <label class="vehicle-radio">
                                <input type="radio" name="vehicle_type" value="Cab">
                                <div class="vehicle-card">
                                    <div class="vehicle-icon">Cab</div>
                                    <div class="vehicle-name">RYDO Cab</div>
                                    <div class="vehicle-rate">₹50 base + ₹16/km</div>
                                    <div class="vehicle-desc">AC comfort</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div id="parcel-details-group" class="parcel-panel">
                        <h4>Receiver &amp; package information</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="receiver_name">Receiver Name</label>
                                <input type="text" id="receiver_name" name="receiver_name" class="form-control" placeholder="Recipient's full name">
                            </div>
                            <div class="form-group">
                                <label for="receiver_phone">Receiver Mobile</label>
                                <input type="tel" id="receiver_phone" name="receiver_phone" class="form-control" placeholder="10-digit mobile">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="parcel_weight">Package Weight (Max 5.0 kg)</label>
                                <input type="number" id="parcel_weight" name="parcel_weight" class="form-control" min="0.1" max="5.0" step="0.1" value="1.0">
                            </div>
                            <div class="form-group">
                                <label for="receiver_address">Delivery Landmark / Flat No.</label>
                                <input type="text" id="receiver_address" name="receiver_address" class="form-control" placeholder="e.g. Near Campus Gate 2">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="payment_method">Payment Mode</label>
                        <select id="payment_method" name="payment_method" class="form-control">
                            <option value="UPI">UPI / QR Code (Google Pay, PhonePe, Paytm)</option>
                            <option value="Cash">Cash to Captain</option>
                            <option value="Wallet">RYDO Digital Wallet</option>
                            <option value="Card">Debit / Credit Card</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Estimated Trip Fare</label>
                        <div class="fare-preview" id="fare-preview">
                            <p class="text-muted">Select pickup and drop location to view estimated fare.</p>
                        </div>
                    </div>

                    <input type="hidden" id="distance_km" name="distance_km" value="">
                    <input type="hidden" id="fare_estimate" name="fare_estimate" value="">

                    <button type="submit" id="submit-btn" class="btn btn-primary btn-block">
                        Confirm &amp; Request Captain
                    </button>
                </form>
            </div>

            <div class="stack">
                <div class="card">
                    <h3 class="card-heading">Kolhapur service corridors</h3>
                    <p class="card-copy">Pickup and drop points available across key hubs:</p>
                    <div class="chip-list">
                        <?php foreach ($locations as $loc): ?>
                            <span class="chip"><?= htmlspecialchars($loc) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="card">
                    <h3 class="card-heading">Safety and trip rules</h3>
                    <ul class="feature-list">
                        <li><strong>4-digit PIN:</strong> Share the code only when the captain arrives.</li>
                        <li><strong>Verified captains:</strong> License and KYC checks are reviewed by admin.</li>
                        <li><strong>SOS:</strong> Emergency alert is available on the trip status page.</li>
                        <li><strong>Parcels:</strong> Delivery confirmation is recorded on the trip.</li>
                    </ul>
                </div>

                <div class="card">
                    <h3 class="card-heading">Other portals</h3>
                    <div class="stack-sm">
                        <a href="captain/login.php" class="btn btn-secondary">Captain partner app</a>
                        <a href="admin/login.php" class="btn btn-secondary">Admin control center</a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<footer>
    <div class="container">
        <p><strong>RYDO</strong> — On-Demand Bike Taxi &amp; Intra-City Logistics Platform</p>
        <p class="team-credits">
            Academic Project: Mini-Project-I (Course Code: UCBIL0371) | Department of CSBS<br>
            <strong>Kolhapur Institute of Technology's College of Engineering (KITCoEK)</strong>
        </p>
        <p class="footer-note">
            Team: Aryan Chougule (06) • Sainath Chavan (05) • Sumeet Keroor (25) • Varad Akolkar (02) • Atif Gulbarga (34) | Guide: Prof. Sheetal Gaikwad
        </p>
    </div>
</footer>

<script src="assets/js/main.js"></script>
</body>
</html>
