<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/config/db.php';

// Redirect only when a valid session already exists.
if (!empty($_SESSION['admin_id'])) {
    header('Location: admin/dashboard.php');
    exit;
}
if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

if (empty($_SESSION['auth_csrf'])) {
    $_SESSION['auth_csrf'] = bin2hex(random_bytes(32));
}

$error = '';
$identity = '';
$role = $_POST['role'] ?? ($_GET['role'] ?? 'user');
$role = $role === 'admin' ? 'admin' : 'user';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = (($_POST['role'] ?? 'user') === 'admin') ? 'admin' : 'user';
    $identity = trim((string)($_POST['identity'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $token = (string)($_POST['csrf_token'] ?? '');

    if (!isset($_SESSION['auth_csrf']) || !hash_equals($_SESSION['auth_csrf'], $token)) {
        $error = 'Session expired. Refresh the page and try again.';
    } elseif ($identity === '' || $password === '') {
        $error = 'Enter your ' . ($role === 'admin' ? 'username' : 'email') . ' and password.';
    } elseif ($role === 'user' && !filter_var(strtolower($identity), FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } else {
        try {
            $pdo = getDB();

            if ($role === 'admin') {
                $stmt = $pdo->prepare('SELECT id, username, password FROM admins WHERE username = ? LIMIT 1');
                $stmt->execute([$identity]);
                $account = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($account && is_string($account['password']) && password_verify($password, $account['password'])) {
                    session_regenerate_id(true);
                    unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email']);
                    $_SESSION['admin_id'] = (int)$account['id'];
                    $_SESSION['admin_username'] = $account['username'];
                    unset($_SESSION['auth_csrf']);
                    header('Location: admin/dashboard.php');
                    exit;
                }
            } else {
                $email = strtolower($identity);
                $stmt = $pdo->prepare('SELECT id, full_name, email, password FROM users WHERE email = ? LIMIT 1');
                $stmt->execute([$email]);
                $account = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($account && is_string($account['password']) && password_verify($password, $account['password'])) {
                    session_regenerate_id(true);
                    unset($_SESSION['admin_id'], $_SESSION['admin_username']);
                    $_SESSION['user_id'] = (int)$account['id'];
                    $_SESSION['user_name'] = $account['full_name'];
                    $_SESSION['user_email'] = $account['email'];
                    unset($_SESSION['auth_csrf']);
                    header('Location: index.php');
                    exit;
                }
            }

            $error = 'Invalid ' . ($role === 'admin' ? 'username' : 'email') . ' or password.';
        } catch (PDOException $e) {
            error_log('RYDO login error: ' . $e->getMessage());
            $error = 'Could not access the account database. Check the database connection and required tables.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login | RYDO</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<main class="auth-page">
<section class="auth-card">
    <div class="auth-brand"><span>RYDO</span></div>
    <h1>Welcome back</h1>
    <p class="auth-sub">Log in to continue with your rides.</p>

    <?php if ($error !== ''): ?>
        <div class="alert alert-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="post" action="login.php">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['auth_csrf'], ENT_QUOTES, 'UTF-8') ?>">

        <div class="auth-switch">
            <label><input type="radio" name="role" value="user" <?= $role === 'user' ? 'checked' : '' ?> onchange="updateIdentity()"> Customer</label>
            <label><input type="radio" name="role" value="admin" <?= $role === 'admin' ? 'checked' : '' ?> onchange="updateIdentity()"> Admin</label>
        </div>

        <label id="identity-label" for="identity"><?= $role === 'admin' ? 'Admin username' : 'Email address' ?></label>
        <input class="form-control" id="identity" name="identity"
               type="<?= $role === 'admin' ? 'text' : 'email' ?>"
               value="<?= htmlspecialchars($identity, ENT_QUOTES, 'UTF-8') ?>"
               placeholder="<?= $role === 'admin' ? 'Enter admin username' : 'you@example.com' ?>"
               autocomplete="username" required>

        <label for="password">Password</label>
        <input class="form-control" id="password" name="password" type="password" autocomplete="current-password" required>

        <button class="btn btn-primary" type="submit">Log In</button>
    </form>
    <p class="auth-foot">Don't have a customer account? <a href="signin.php">Sign in / Create account</a></p>
    <p class="auth-foot"><a href="captain/login.php">Captain partner login</a></p>
</section>
</main>
<script>
function updateIdentity() {
    const admin = document.querySelector('input[name="role"]:checked').value === 'admin';
    const identity = document.getElementById('identity');
    document.getElementById('identity-label').textContent = admin ? 'Admin username' : 'Email address';
    identity.type = admin ? 'text' : 'email';
    identity.placeholder = admin ? 'Enter admin username' : 'you@example.com';
    identity.value = '';
}
</script>
</body>
</html>
