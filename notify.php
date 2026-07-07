<?php
/**
 * Visitor Notification System
 *
 * Sends a Telegram notification on every website visit.
 * Called as a tracking pixel from the frontend.
 */

// ── Load Configuration ──────────────────────────────────────────────────────
$configPath = __DIR__ . '/../config/telegram-config.php';
if (file_exists($configPath)) {
    require_once $configPath;
}

if (!defined('TELEGRAM_BOT_TOKEN')) {
    define('TELEGRAM_BOT_TOKEN', getenv('TELEGRAM_BOT_TOKEN') ?: '8989741457:AAHRcgJMaYBuOLsIKT-Dk1Ct9tbJh_Uqi7M');
}
if (!defined('TELEGRAM_CHAT_ID')) {
    define('TELEGRAM_CHAT_ID', getenv('TELEGRAM_CHAT_ID') ?: '-1004399226073');
}
if (!defined('TELEGRAM_LOG_FILE')) {
    define('TELEGRAM_LOG_FILE', __DIR__ . '/telegram-errors.log');
}

// ── Duplicate Prevention (session-based cooldown) ──────────────────────────
$cooldown = 5;
$sendNotification = true;

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
if (isset($_SESSION['last_notify_ts']) && (time() - $_SESSION['last_notify_ts']) < $cooldown) {
    $sendNotification = false;
}
$_SESSION['last_notify_ts'] = time();
session_write_close();

// ── Sanitization Helper (for HTML parse_mode) ──────────────────────────────
function sanitize($text) {
    return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// ── Visitor Data Collection ────────────────────────────────────────────────
$ip        = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$referrer  = $_SERVER['HTTP_REFERER'] ?? '';
$browserLang = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'unknown';
$protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host      = $_SERVER['HTTP_HOST'] ?? 'unknown';
$uri       = $_SERVER['REQUEST_URI'] ?? '/';
$currentUrl = $protocol . '://' . $host . $uri;

// ── Device Type ────────────────────────────────────────────────────────────
if (preg_match('/tablet|ipad|playbook|silk|android(?!.*mobile)/i', $userAgent)) {
    $deviceType = 'Tablet';
} elseif (preg_match('/mobile|iphone|ipod|blackberry|webos|opera mini|iemobile|wp desktop/i', $userAgent)) {
    $deviceType = 'Mobile';
} else {
    $deviceType = 'Desktop';
}

// ── Browser Name ───────────────────────────────────────────────────────────
if (preg_match('/Edg\/([\d.]+)/i', $userAgent, $m)) {
    $browserName = 'Edge ' . $m[1];
} elseif (preg_match('/OPR\/([\d.]+)/i', $userAgent, $m)) {
    $browserName = 'Opera ' . $m[1];
} elseif (preg_match('/Firefox\/([\d.]+)/i', $userAgent, $m)) {
    $browserName = 'Firefox ' . $m[1];
} elseif (preg_match('/Chrome\/([\d.]+)/i', $userAgent, $m)) {
    $browserName = 'Chrome ' . $m[1];
} elseif (preg_match('/Safari\/([\d.]+)/i', $userAgent, $m)) {
    $browserName = 'Safari ' . $m[1];
} else {
    $browserName = 'Unknown';
}

// ── Operating System ───────────────────────────────────────────────────────
if (preg_match('/Windows NT ([\d.]+)/i', $userAgent, $m)) {
    $winVer = ['10.0' => '10', '6.3' => '8.1', '6.2' => '8', '6.1' => '7', '6.0' => 'Vista'];
    $os = 'Windows ' . ($winVer[$m[1]] ?? $m[1]);
} elseif (preg_match('/Mac OS X ([\d_]+)/i', $userAgent, $m)) {
    $os = 'macOS ' . str_replace('_', '.', $m[1]);
} elseif (preg_match('/Android ([\d.]+)/i', $userAgent, $m)) {
    $os = 'Android ' . $m[1];
} elseif (preg_match('/(?:iPhone OS|iOS) ([\d_]+)/i', $userAgent, $m)) {
    $os = 'iOS ' . str_replace('_', '.', $m[1]);
} elseif (preg_match('/Linux/i', $userAgent)) {
    $os = 'Linux';
} else {
    $os = 'Unknown';
}

// ── Geolocation ────────────────────────────────────────────────────────────
$country = 'Unknown';
$city    = 'Unknown';
if ($ip !== 'unknown' && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
    $geoJson = @file_get_contents('http://ip-api.com/json/' . rawurlencode($ip) . '?fields=city,country,query', false,
        stream_context_create(['http' => ['timeout' => 3, 'method' => 'GET']]));
    if ($geoJson) {
        $geo = @json_decode($geoJson, true);
        if ($geo && ($geo['query'] ?? '') === $ip) {
            $country = $geo['country'] ?? 'Unknown';
            $city    = $geo['city'] ?? 'Unknown';
        }
    }
}

// ── Build Message ──────────────────────────────────────────────────────────
$message  = "\u{1F310} <b>New Website Visitor</b>\n\n";
$message .= "\u{1F4C5} Date: " . date('Y-m-d') . "\n";
$message .= "\u{1F552} Time: " . date('H:i:s') . "\n";
$message .= "\u{1F30D} IP Address: " . sanitize($ip) . "\n";
$message .= "\u{1F4C4} Visited URL: " . sanitize($currentUrl) . "\n";
$message .= "\u{1F4CC} Page Title: Nas Digital\n";
$message .= "\u{1F310} Referrer: " . ($referrer ? sanitize($referrer) : 'Direct') . "\n";
$message .= "\u{1F4BB} Device: " . $deviceType . "\n";
$message .= "\u{1F5A5} Browser: " . $browserName . "\n";
$message .= "\u{2699} Operating System: " . $os . "\n";
$message .= "\u{1F30E} Country: " . sanitize($country) . "\n";
$message .= "\u{1F3D9} City: " . sanitize($city) . "\n";
$message .= "\u{1F5E3} Language: " . sanitize(explode(',', $browserLang)[0]) . "\n";
$message .= "\u{23F1} Server Time: " . date('Y-m-d H:i:s');

// ── Send Notification ──────────────────────────────────────────────────────
if ($sendNotification) {
    $url = 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN . '/sendMessage';
    $data = [
        'chat_id'    => TELEGRAM_CHAT_ID,
        'text'       => $message,
        'parse_mode' => 'HTML',
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT      => 'NasDigital-Notifier/1.0',
    ]);
    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($httpCode !== 200) {
        $logLine = date('Y-m-d H:i:s')
                 . ' | HTTP:' . $httpCode
                 . ' | IP:' . $ip
                 . ' | URL:' . $currentUrl
                 . ' | Error: ' . ($curlError ?: 'No cURL error')
                 . ' | Response: ' . substr($response ?? '', 0, 200)
                 . "\n";
        @file_put_contents(TELEGRAM_LOG_FILE, $logLine, FILE_APPEND | LOCK_EX);
    }
}

// ── Output Tracking GIF ────────────────────────────────────────────────────
header('Content-Type: image/gif');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Length: 43');
echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
