<?php
/**
 * captain/login.php — Captain Partner login.
 *
 * Authenticates against the existing `captains` table using the phone
 * number + license number already stored on every captain record, so no
 * schema changes are required. (For a production build you'd add a real
 * `password` column and hash it like `admins`/`users` do — this keeps the
 * same two-factor-by-existing-data approach the rest of the project uses.)
 */

if (session_status() === PHP_SESSION_NONE) session_start();

if (!empty($_SESSION['captain_id'])) {
    header('Location: dashboard.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

if (empty($_SESSION['captain_csrf'])) $_SESSION['captain_csrf'] = bin2hex(random_bytes(32));
$error = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone   = trim($_POST['phone'] ?? '');
    $license = trim($_POST['license_number'] ?? '');
    $token   = (string)($_POST['csrf_token'] ?? '');

    if (!hash_equals($_SESSION['captain_csrf'], $token)) {
        $error = 'Session expired. Refresh the page and try again.';
    } elseif ($phone === '' || $license === '') {
        $error = 'Enter your registered mobile number and license number.';
    } else {
        try {
            $pdo  = getDB();
            $stmt = $pdo->prepare('SELECT id, name, phone, license_number FROM captains WHERE phone = ? LIMIT 1');
            $stmt->execute([$phone]);
            $captain = $stmt->fetch();

            if ($captain && hash_equals(strtoupper($captain['license_number']), strtoupper($license))) {
                session_regenerate_id(true);
                $_SESSION['captain_id']   = (int)$captain['id'];
                $_SESSION['captain_name'] = $captain['name'];
                unset($_SESSION['captain_csrf']);
                header('Location: dashboard.php');
                exit;
            }
            sleep(1);
            $error = 'Invalid mobile number or license number.';
        } catch (PDOException $e) {
            error_log('RYDO captain login error: ' . $e->getMessage());
            $error = 'Could not access the account database. Check that MySQL is running.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Captain Login — RYDO</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="admin-body">

<div class="login-wrapper">
    <div class="card login-card">

        <div class="login-logo">RYDO</div>
        <h1 class="login-title">Captain login</h1>
        <p class="login-sub">Partner portal — accept trips and track earnings</p>

        <?php if ($error): ?>
            <div class="alert alert-error" role="alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST" id="captain-login-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['captain_csrf'], ENT_QUOTES, 'UTF-8') ?>">

            <div class="form-group">
                <label for="phone">Registered Mobile Number</label>
                <input type="tel" id="phone" name="phone"
                       class="form-control" placeholder="10-digit mobile number"
                       maxlength="10" value="<?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?>" required>
            </div>

            <div class="form-group">
                <label for="license_number">License Number</label>
                <input type="text" id="license_number" name="license_number"
                       class="form-control" placeholder="As on your driving license"
                       autocomplete="off" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                Log in to Captain App
            </button>
        </form>

        <p class="auth-foot">
            <a href="../index.php">Back to booking page</a>
        </p>

    </div>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>
