<?php
/**
 * Nas Digital AI Chatbot - Installation Script
 * 
 * Run this file once to set up database tables.
 * Delete or secure this file after installation.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

header('Content-Type: text/html; charset=utf-8');

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    try {
        // Create database if not exists
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo = null;

        // Create tables
        Database::createTables();
        $success = 'Database and tables created successfully!';
    } catch (Exception $e) {
        $error = 'Installation failed: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chatbot Installation - Nas Digital</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: #050505;
            color: #f5f0e0;
            font-family: 'Rajdhani', -apple-system, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .card {
            max-width: 560px;
            width: 100%;
            background: #0a0a0a;
            border: 1px solid rgba(201, 162, 39, 0.18);
            border-radius: 20px;
            padding: 40px 32px;
            box-shadow: 0 24px 60px rgba(0,0,0,0.5);
        }
        h1 {
            font-size: 1.8rem;
            color: #e8d48b;
            text-align: center;
            margin-bottom: 8px;
            letter-spacing: 1px;
        }
        .sub {
            text-align: center;
            color: #6b6055;
            margin-bottom: 28px;
            font-size: 0.95rem;
        }
        .info {
            background: rgba(201, 162, 39, 0.06);
            border: 1px solid rgba(201, 162, 39, 0.12);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            color: #b8a98a;
            line-height: 1.6;
        }
        .info strong {
            color: #c9a227;
        }
        .btn {
            display: block;
            width: 100%;
            padding: 14px;
            border-radius: 14px;
            border: 1px solid rgba(201, 162, 39, 0.35);
            background: linear-gradient(135deg, rgba(201,162,39,0.15), rgba(201,162,39,0.06));
            color: #e8d48b;
            font-family: inherit;
            font-size: 1.05rem;
            font-weight: 700;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
        }
        .btn:hover {
            border-color: #c9a227;
            box-shadow: 0 6px 22px rgba(201,162,39,0.18);
            transform: translateY(-2px);
        }
        .success {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #4ade80;
            border-radius: 12px;
            padding: 14px;
            margin-bottom: 16px;
            text-align: center;
        }
        .error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #f87171;
            border-radius: 12px;
            padding: 14px;
            margin-bottom: 16px;
            text-align: center;
        }
        ul {
            list-style: none;
            padding: 0;
        }
        ul li {
            padding: 6px 0;
            color: #6b6055;
            font-size: 0.88rem;
        }
        ul li::before {
            content: '>';
            color: #c9a227;
            margin-right: 8px;
        }
        .footer {
            margin-top: 24px;
            text-align: center;
            font-size: 0.8rem;
            color: #6b6055;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>Nas Digital Chatbot</h1>
        <p class="sub">Database Installation</p>

        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="info">
            <strong>Database:</strong> <?= htmlspecialchars(DB_NAME) ?><br>
            <strong>Host:</strong> <?= htmlspecialchars(DB_HOST) ?><br>
            <strong>User:</strong> <?= htmlspecialchars(DB_USER) ?><br><br>
            <strong>Tables to be created:</strong>
            <ul>
                <li>visitors - Store visitor information</li>
                <li>conversations - Track chat sessions</li>
                <li>messages - Save all messages</li>
                <li>human_requests - Human support requests</li>
            </ul>
        </div>

        <form method="post">
            <button type="submit" name="install" class="btn">Install Database</button>
        </form>

        <div class="footer">
            Delete this file after installation for security.
        </div>
    </div>
</body>
</html>
