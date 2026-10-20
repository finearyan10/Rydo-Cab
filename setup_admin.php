<?php
/**
 * setup_admin.php — Run once to repair/create the admin account.
 *
 * Open: http://localhost/OnlineCabBooking/setup_admin.php
 * After confirming login works, DELETE this file.
 */

require_once __DIR__ . '/config/db.php';

$username = 'admin';
$password = 'Admin@1234';
$verified = false;
$setup_err = '';

try {
    $pdo = getDB();

    // Create the table if it does not exist.
    $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(60) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // IMPORTANT: CREATE TABLE IF NOT EXISTS does not repair an existing
    // table, so explicitly widen a previously truncated password column.
    $pdo->exec("ALTER TABLE admins MODIFY COLUMN password VARCHAR(255) NOT NULL");

    // Generate a fresh password hash and insert/update the admin account.
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare(
        'INSERT INTO admins (username, password)
         VALUES (:username, :hash)
         ON DUPLICATE KEY UPDATE password = :hash2'
    );
    $stmt->execute([
        ':username' => $username,
        ':hash' => $hash,
        ':hash2' => $hash
    ]);

    // Verify the stored hash after the database write.
    $check = $pdo->prepare('SELECT id, username, password FROM admins WHERE username = ? LIMIT 1');
    $check->execute([$username]);
    $row = $check->fetch(PDO::FETCH_ASSOC);
    $verified = $row && password_verify($password, $row['password']);

    if (!$verified) {
        $setup_err = 'The admin account was written, but its password hash could not be verified. Check the database column and connection.';
    }
} catch (Throwable $e) {
    error_log('RYDO admin setup error: ' . $e->getMessage());
    $setup_err = 'Setup failed. Check config/db.php and the MySQL/PHP error log. Details: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Setup — RYDO</title>
<style>
    body { font-family: Arial, sans-serif; background:#0d0f14; color:#e8eaf0; display:flex; align-items:center; justify-content:center; min-height:100vh; margin:0; padding:20px; box-sizing:border-box; }
    .box { background:#161a24; border:1px solid rgba(255,255,255,.1); border-radius:14px; padding:2rem; width:100%; max-width:560px; text-align:center; box-sizing:border-box; }
    h1 { color:#f5a623; }
    .ok { color:#27ae60; font-size:3rem; }
    .err { color:#e74c3c; font-size:3rem; }
    code { background:rgba(255,255,255,.07); padding:.2rem .5rem; border-radius:4px; }
    a { color:#f5a623; }
    .errmsg { text-align:left; background:rgba(231,76,60,.08); border:1px solid rgba(231,76,60,.3); border-radius:8px; padding:.8rem 1rem; color:#ff8ba8; margin-top:1rem; overflow-wrap:anywhere; }
    .warning { color:#ffcc80; font-size:.9rem; }
</style>
</head>
<body>
<div class="box">
<?php if ($verified): ?>
    <div class="ok">✓</div>
    <h1>Admin Account Ready</h1>
    <p>The <code>admins</code> table is repaired and the password hash verifies correctly.</p>
    <p>Username: <code>admin</code><br>Password: <code>Admin@1234</code></p>
    <p><a href="login.php?role=admin">Go to Admin Login</a></p>
    <p class="warning">Security: delete <code>setup_admin.php</code> from your project folder after testing.</p>
<?php else: ?>
    <div class="err">✗</div>
    <h1>Setup Failed</h1>
    <p>The admin account could not be created or verified.</p>
    <?php if ($setup_err !== ''): ?>
        <div class="errmsg"><?= htmlspecialchars($setup_err, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <p>Check your database connection in <code>config/db.php</code> and ensure the MySQL user can alter the <code>admins</code> table.</p>
<?php endif; ?>
</div>
</body>
</html>
