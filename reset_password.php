<?php
// reset_password.php - Force Reset Password
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Password Reset Tool</h1>";

// 1. Connect to DB
try {
    // Robust .env parser
    $env = [];
    if (file_exists(__DIR__ . '/.env')) {
        $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0)
                continue;

            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $key = trim($parts[0]);
                $value = trim($parts[1]);
                if (preg_match('/^"(.*)"$/', $value, $m))
                    $value = $m[1];
                elseif (preg_match("/^'(.*)'$/", $value, $m))
                    $value = $m[1];
                $env[$key] = $value;
            }
        }
    }

    $dsn = "mysql:host=" . ($env['DB_HOST'] ?? '127.0.0.1') . ";dbname=" . ($env['DB_DATABASE'] ?? '');
    $pdo = new PDO($dsn, $env['DB_USERNAME'] ?? '', $env['DB_PASSWORD'] ?? '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<span style='color:green'>[✔] Database connected.</span><br>";
} catch (PDOException $e) {
    die("<span style='color:red'>[✖] Database Connection FAILED: " . $e->getMessage() . "</span>");
}

// 2. Define User and New Password
$userId = 2; // Team Work®
$newPassword = '123456';

// 3. Generate New Hash
$newSalt = substr(md5(uniqid(rand(), true)), 0, 30); // Generate random 30-char salt
$newHash = md5(md5($newPassword) . $newSalt);

echo "<hr>";
echo "Target User ID: <b>$userId</b><br>";
echo "New Password: <b>$newPassword</b><br>";
echo "New Salt: <b>$newSalt</b><br>";
echo "New Hash: <b>$newHash</b><br>";
echo "<hr>";

// 4. Update Database
try {
    $stmt = $pdo->prepare("UPDATE user SET password = :password, salt = :salt WHERE userid = :userid");
    $stmt->execute([
        'password' => $newHash,
        'salt' => $newSalt,
        'userid' => $userId
    ]);

    echo "<h2 style='color:green'>[✔] Password Updated Successfully!</h2>";
    echo "You can now login with password: <b>123456</b><br>";
    echo "After login, you can change it from your profile.";
} catch (PDOException $e) {
    echo "<h2 style='color:red'>[✖] Update Failed: " . $e->getMessage() . "</h2>";
}
