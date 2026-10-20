<?php
// config/db.php — PDO database connection for RYDO Platform

define('DB_HOST', 'localhost');
define('DB_NAME', 'rydo_db');      // Main database from SRS
define('DB_FALLBACK', 'cab_booking'); // Fallback if user imported under previous name
define('DB_USER', 'root');         // Default XAMPP MySQL user
define('DB_PASS', '');             // Default XAMPP MySQL password (empty)
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        // Try primary database `rydo_db`
        try {
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e1) {
            // Try fallback database `cab_booking`
            try {
                $dsnFallback = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_FALLBACK, DB_CHARSET);
                $pdo = new PDO($dsnFallback, DB_USER, DB_PASS, $options);
            } catch (PDOException $e2) {
                // If neither exists, attempt to connect to MySQL server root to auto-create rydo_db
                try {
                    $serverDsn = sprintf('mysql:host=%s;charset=%s', DB_HOST, DB_CHARSET);
                    $serverPdo = new PDO($serverDsn, DB_USER, DB_PASS, $options);
                    $serverPdo->exec("CREATE DATABASE IF NOT EXISTS `rydo_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
                    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
                    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
                } catch (PDOException $e3) {
                    die('<div style="font-family:sans-serif;padding:2rem;background:#161a24;color:#e8eaf0;max-width:600px;margin:3rem auto;border-radius:12px;border:1px solid #c0392b;">
                         <h2 style="color:#e74c3c;">RYDO Database Connection Error</h2>
                         <p>Could not connect to MySQL server or database <code>rydo_db</code>.</p>
                         <p><strong>Troubleshooting:</strong></p>
                         <ol>
                           <li>Make sure <strong>MySQL</strong> is started in the XAMPP Control Panel.</li>
                           <li>Import <code>database.sql</code> via <a href="http://localhost/phpmyadmin" style="color:#f5a623;">phpMyAdmin</a>.</li>
                           <li>Check user credentials in <code>config/db.php</code>.</li>
                         </ol>
                         <p style="font-size:0.85rem;color:#8892a4;">Error: ' . htmlspecialchars($e1->getMessage()) . '</p>
                         </div>');
                }
            }
        }
    }
    return $pdo;
}
