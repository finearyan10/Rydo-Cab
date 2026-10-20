<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/config/db.php';
// Keep the sign-up page accessible even when a customer is already logged in,
// so they can create/switch to another account from the same browser session.
if (empty($_SESSION['signup_csrf'])) $_SESSION['signup_csrf'] = bin2hex(random_bytes(32));
$error = ''; $name = ''; $email = ''; $phone = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string)($_POST['full_name'] ?? ''));
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $phone = trim((string)($_POST['phone'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');
    $token = (string)($_POST['csrf_token'] ?? '');
    if (!hash_equals($_SESSION['signup_csrf'], $token)) $error = 'Session expired. Refresh the page and try again.';
    elseif ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') $error = 'Enter your name, a valid email, and a password.';
    elseif (strlen($password) < 6) $error = 'Password must be at least 6 characters.';
    elseif ($password !== $confirm) $error = 'Passwords do not match.';
    else {
        try {
            $pdo = getDB();
            // Ensure this customer table exists even if the original SQL did not include it.
            $pdo->exec("CREATE TABLE IF NOT EXISTS users (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                full_name VARCHAR(100) NOT NULL,
                email VARCHAR(150) NOT NULL UNIQUE,
                phone VARCHAR(20) DEFAULT NULL,
                password VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            $check = $pdo->prepare('SELECT id, full_name, email, password FROM users WHERE email = ? LIMIT 1');
            $check->execute([$email]);
            $existing = $check->fetch();

            if ($existing) {
                // Existing email: treat this as login, never create a duplicate account.
                if (password_verify($password, $existing['password'])) {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = (int)$existing['id'];
                    $_SESSION['user_name'] = $existing['full_name'];
                    $_SESSION['user_email'] = $existing['email'];
                    unset($_SESSION['signup_csrf']);
                    header('Location: index.php'); exit;
                }
                $error = 'This email already has an account. Enter its correct password or use Log In.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO users (full_name, email, phone, password) VALUES (?, ?, ?, ?)');
                $stmt->execute([$name, $email, $phone !== '' ? $phone : null, password_hash($password, PASSWORD_DEFAULT)]);
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int)$pdo->lastInsertId();
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                unset($_SESSION['signup_csrf']);
                header('Location: index.php'); exit;
            }
        } catch (PDOException $e) {
            error_log('RYDO registration error: ' . $e->getMessage());
            $error = 'Account creation failed. Check MySQL is running and try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create Account | RYDO</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<main class="auth-page">
<section class="auth-card">
    <div class="auth-brand"><span>RYDO</span></div>
    <h1>Create your account</h1>
    <p class="auth-sub">Sign up once, then log in whenever you return.</p>

    <?php if ($error !== ''): ?>
        <div class="alert alert-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="post" action="signin.php">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['signup_csrf'], ENT_QUOTES, 'UTF-8') ?>">

        <div class="form-group">
            <label for="full_name">Full name</label>
            <input class="form-control" id="full_name" name="full_name" type="text" maxlength="100" autocomplete="name" value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" required>
        </div>
        <div class="form-group">
            <label for="email">Email address</label>
            <input class="form-control" id="email" name="email" type="email" maxlength="150" autocomplete="email" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" required>
        </div>
        <div class="form-group">
            <label for="phone">Phone (optional)</label>
            <input class="form-control" id="phone" name="phone" type="tel" maxlength="20" autocomplete="tel" value="<?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="form-group">
            <label for="password">Password (at least 6 characters)</label>
            <input class="form-control" id="password" name="password" type="password" minlength="6" autocomplete="new-password" required>
        </div>
        <div class="form-group">
            <label for="confirm_password">Confirm password</label>
            <input class="form-control" id="confirm_password" name="confirm_password" type="password" minlength="6" autocomplete="new-password" required>
        </div>
        <button class="btn btn-primary" type="submit">Create Account</button>
    </form>
    <p class="auth-foot">Already have an account? <a href="login.php">Log in</a></p>
</section>
</main>
</body>
</html>
