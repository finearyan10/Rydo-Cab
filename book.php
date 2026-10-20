<?php
/**
 * book.php — Handle Ride & RYDO Parcel Booking Request
 * Generates 4-digit OTP PIN, calculates platform commission, and assigns captain
 */

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$service_type   = trim($_POST['service_type'] ?? 'ride');
$name           = trim($_POST['passenger_name'] ?? '');
$phone          = trim($_POST['phone'] ?? '');
$pickup         = trim($_POST['pickup'] ?? '');
$destination    = trim($_POST['destination'] ?? '');
$vehicle_type   = trim($_POST['vehicle_type'] ?? 'Bike');
$payment_method = trim($_POST['payment_method'] ?? 'UPI');

// Parcel specific fields
$receiver_name    = trim($_POST['receiver_name'] ?? '');
$receiver_phone   = trim($_POST['receiver_phone'] ?? '');
$receiver_address = trim($_POST['receiver_address'] ?? '');
$parcel_weight    = floatval($_POST['parcel_weight'] ?? 1.0);

$errors = [];

if (empty($name) || strlen($name) < 2) {
    $errors[] = 'Please enter a valid passenger/sender name.';
}
if (!preg_match('/^[0-9]{10}$/', $phone)) {
    $errors[] = 'Please enter a valid 10-digit mobile number.';
}
if (empty($pickup) || empty($destination)) {
    $errors[] = 'Please select both pickup and drop locations.';
}
if ($pickup === $destination) {
    $errors[] = 'Pickup and drop locations cannot be the same.';
}

if ($service_type === 'parcel') {
    $vehicle_type = 'Parcel';
    if (empty($receiver_name)) {
        $errors[] = 'Please provide the receiver\'s name.';
    }
    if (!preg_match('/^[0-9]{10}$/', $receiver_phone)) {
        $errors[] = 'Please provide a valid 10-digit receiver contact number.';
    }
    if ($parcel_weight <= 0 || $parcel_weight > 5.0) {
        $errors[] = 'RYDO Parcel allows packages up to a maximum weight of 5.0 kg.';
    }
}

// Distance Calculation
$routes = require __DIR__ . '/config/routes.php';
$fares  = require __DIR__ . '/config/fares.php';

$key1 = $pickup . '|' . $destination;
$key2 = $destination . '|' . $pickup;
$distance_km = $routes[$key1] ?? $routes[$key2] ?? 6.0;

// Recompute accurate server-side fare
$fare_config = $fares[$vehicle_type] ?? $fares['Bike'];
$extra_km    = max(0, $distance_km - $fare_config['base_km']);
$total_fare  = $fare_config['base_fare'] + ($extra_km * $fare_config['per_km']);

// Platform commission (20% as per SRS Section 5.5)
$commission_rate = 0.20;
$commission_amt  = round($total_fare * $commission_rate, 2);
$captain_earning = round($total_fare - $commission_amt, 2);

if (!empty($errors)) {
    $_SESSION['booking_error'] = implode(' ', $errors);
    header('Location: index.php');
    exit;
}

// Generate unique Booking ID & 4-Digit Security OTP (SRS Section 4.2)
$prefix     = ($service_type === 'parcel') ? 'RYD-PCL-' : 'RYD-';
$booking_id = $prefix . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
$otp_pin    = str_pad((string)mt_rand(1000, 9999), 4, '0', STR_PAD_LEFT);

$pdo = getDB();

// Assign an available active verified captain matching vehicle type
$captain_id = null;
$vtype_needed = ($vehicle_type === 'Parcel') ? 'Bike' : $vehicle_type;
$captain_stmt = $pdo->prepare(
    "SELECT id FROM captains
     WHERE vehicle_type = :vtype
       AND is_online = 1
       AND kyc_status = 'Approved'
     ORDER BY RAND() LIMIT 1"
);
$captain_stmt->execute([':vtype' => $vtype_needed]);
$assigned_captain = $captain_stmt->fetch();
if ($assigned_captain) {
    $captain_id = $assigned_captain['id'];
}

// Insert into bookings
$insert_stmt = $pdo->prepare(
    "INSERT INTO bookings
     (booking_id, service_type, passenger_name, phone, pickup, destination, vehicle_type,
      distance_km, fare_estimate, commission_amount, captain_earning, payment_method,
      otp_pin, status, captain_id, receiver_name, receiver_phone, receiver_address, parcel_weight)
     VALUES
     (:bid, :stype, :pname, :phone, :pickup, :dest, :vtype,
      :dist, :fare, :comm, :earn, :pmethod,
      :otp, 'Accepted', :cid, :rname, :rphone, :raddr, :weight)"
);

try {
    $insert_stmt->execute([
        ':bid'     => $booking_id,
        ':stype'   => $service_type,
        ':pname'   => $name,
        ':phone'   => $phone,
        ':pickup'  => $pickup,
        ':dest'    => $destination,
        ':vtype'   => $vehicle_type,
        ':dist'    => number_format($distance_km, 2, '.', ''),
        ':fare'    => number_format($total_fare, 2, '.', ''),
        ':comm'    => number_format($commission_amt, 2, '.', ''),
        ':earn'    => number_format($captain_earning, 2, '.', ''),
        ':pmethod' => $payment_method,
        ':otp'     => $otp_pin,
        ':cid'     => $captain_id,
        ':rname'   => ($service_type === 'parcel') ? $receiver_name : null,
        ':rphone'  => ($service_type === 'parcel') ? $receiver_phone : null,
        ':raddr'   => ($service_type === 'parcel') ? $receiver_address : null,
        ':weight'  => ($service_type === 'parcel') ? $parcel_weight : null,
    ]);
} catch (PDOException $e) {
    $_SESSION['booking_error'] = 'Could not record booking: ' . htmlspecialchars($e->getMessage());
    header('Location: index.php');
    exit;
}

header('Location: confirmation.php?bid=' . urlencode($booking_id));
exit;
