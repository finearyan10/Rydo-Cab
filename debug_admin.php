<?php
/**
 * debug_admin.php — ONE-TIME diagnostic for admin login problems.
 *
 * Shows: whether the app can reach the DB, whether `admins` exists,
 * what usernames are in it, and whether password_verify() actually
 * succeeds for 'admin' / 'Admin@1234'. Delete this file once you're
 * done — it's a debugging aid, not something to leave on a live site.
 */

require_once __DIR__ . '/config/db.php';

echo '<pre style="font-family:monospace; background:#111; color:#eee; padding:1.5rem; font-size:14px;">';

try {
    $pdo = getDB();
    echo "✓ Connected to the database.\n\n";

    // Which DB are we actually talking to? (helps catch "wrong database" bugs)
    $dbname = $pdo->query('SELECT DATABASE()')->fetchColumn();
    echo "Connected database: " . htmlspecialchars((string)$dbname) . "\n\n";

    $tables = $pdo->query("SHOW TABLES LIKE 'admins'")->fetchAll();
    if (!$tables) {
        echo "✗ The `admins` table does NOT exist in this database.\n";
        echo "  -> Run setup_admin.php again — it should create it.\n";
    } else {
        echo "✓ `admins` table exists.\n\n";

        $rows = $pdo->query('SELECT id, username, LENGTH(password) AS hash_len, password FROM admins')->fetchAll();
        if (!$rows) {
            echo "✗ `admins` table is EMPTY — no rows at all.\n";
            echo "  -> Run setup_admin.php again.\n";
        } else {
            echo "Rows in `admins` (" . count($rows) . "):\n";
            foreach ($rows as $r) {
                echo "  id={$r['id']}  username=" . htmlspecialchars($r['username']) . "  hash_len={$r['hash_len']}\n";
            }
            echo "\n";

            $admin_row = null;
            foreach ($rows as $r) {
                if ($r['username'] === 'admin') { $admin_row = $r; break; }
            }

            if (!$admin_row) {
                echo "✗ No row has username exactly 'admin' (check for typos/extra spaces/case).\n";
            } else {
                if ((int)$admin_row['hash_len'] < 60) {
                    echo "✗ The stored hash is only {$admin_row['hash_len']} chars — a bcrypt hash needs ~60.\n";
                    echo "  -> Your `password` column is too narrow (needs to be VARCHAR(255)) and is truncating it.\n";
                } else {
                    $ok = password_verify('Admin@1234', $admin_row['password']);
                    echo $ok
                        ? "✓ password_verify('Admin@1234', ...) SUCCEEDS against the stored hash.\n   Login should work with username 'admin' / password 'Admin@1234' exactly.\n"
                        : "✗ password_verify('Admin@1234', ...) FAILS against the stored hash.\n   The row exists but wasn't set with this exact password. Re-run setup_admin.php.\n";
                }
            }
        }
    }
} catch (PDOException $e) {
    echo "✗ Database error: " . htmlspecialchars($e->getMessage()) . "\n";
}

echo '</pre>';