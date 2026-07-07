<?php

function sanitize_input($data): string
{
    if (is_string($data)) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    return $data;
}

function sanitize_message(string $message): string
{
    $message = trim($message);
    $message = mb_substr($message, 0, MAX_MESSAGE_LENGTH);
    return $message;
}

function sanitize_html(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function json_response(mixed $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function error_response(string $message, int $code = 400): void
{
    json_response(['success' => false, 'error' => $message], $code);
}

function get_ip(): string
{
    $headers = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    foreach ($headers as $h) {
        if (!empty($_SERVER[$h])) {
            $ips = explode(',', $_SERVER[$h]);
            $ip = trim($ips[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return '0.0.0.0';
}

function get_browser_name(string $ua): string
{
    if (preg_match('/Edg\/([\d.]+)/i', $ua, $m)) return 'Edge ' . $m[1];
    if (preg_match('/OPR\/([\d.]+)/i', $ua, $m)) return 'Opera ' . $m[1];
    if (preg_match('/Firefox\/([\d.]+)/i', $ua, $m)) return 'Firefox ' . $m[1];
    if (preg_match('/Chrome\/([\d.]+)/i', $ua, $m)) return 'Chrome ' . $m[1];
    if (preg_match('/Safari\/([\d.]+)/i', $ua, $m)) return 'Safari ' . $m[1];
    return 'Unknown';
}

function get_device(string $ua): string
{
    if (preg_match('/tablet|ipad|playbook|silk|android(?!.*mobile)/i', $ua)) return 'Tablet';
    if (preg_match('/mobile|iphone|ipod|blackberry|webos|opera mini|iemobile|wp desktop/i', $ua)) return 'Mobile';
    return 'Desktop';
}

function get_os(string $ua): string
{
    if (preg_match('/Windows NT ([\d.]+)/i', $ua, $m)) {
        $map = ['10.0' => '10', '6.3' => '8.1', '6.2' => '8', '6.1' => '7'];
        return 'Windows ' . ($map[$m[1]] ?? $m[1]);
    }
    if (preg_match('/Mac OS X ([\d_]+)/i', $ua, $m)) return 'macOS ' . str_replace('_', '.', $m[1]);
    if (preg_match('/Android ([\d.]+)/i', $ua, $m)) return 'Android ' . $m[1];
    if (preg_match('/(?:iPhone OS|iOS) ([\d_]+)/i', $ua, $m)) return 'iOS ' . str_replace('_', '.', $m[1]);
    if (preg_match('/Linux/i', $ua)) return 'Linux';
    return 'Unknown';
}

function get_geo(string $ip): array
{
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        $json = @file_get_contents('http://ip-api.com/json/' . rawurlencode($ip) . '?fields=city,country,query', false,
            stream_context_create(['http' => ['timeout' => 2, 'method' => 'GET']]));
        if ($json) {
            $data = @json_decode($json, true);
            if ($data && ($data['query'] ?? '') === $ip) {
                return [
                    'country' => $data['country'] ?? 'Unknown',
                    'city' => $data['city'] ?? 'Unknown',
                ];
            }
        }
    }
    return ['country' => 'Unknown', 'city' => 'Unknown'];
}

function generate_session_id(): string
{
    return bin2hex(random_bytes(32));
}

function get_or_create_session(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    if (empty($_SESSION['chatbot_session_id'])) {
        $_SESSION['chatbot_session_id'] = generate_session_id();
    }
    return $_SESSION['chatbot_session_id'];
}

function check_rate_limit(string $identifier): bool
{
    $file = __DIR__ . '/logs/ratelimit_' . md5($identifier) . '.lock';
    $now = time();

    $data = [];
    if (file_exists($file)) {
        $data = @json_decode(@file_get_contents($file), true) ?: [];
        $data = array_filter($data, fn($t) => ($now - $t) < RATE_LIMIT_WINDOW);
    }

    if (count($data) >= RATE_LIMIT_MAX) {
        return false;
    }

    $data[] = $now;
    @file_put_contents($file, json_encode($data), LOCK_EX);
    return true;
}

function log_error(string $message, array $context = []): void
{
    $log = date('Y-m-d H:i:s') . ' | ' . $message;
    if (!empty($context)) {
        $log .= ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE);
    }
    $log .= "\n";
    @file_put_contents(CHATBOT_LOG_FILE, $log, FILE_APPEND | LOCK_EX);
}

function format_timestamp(string $datetime): string
{
    $ts = strtotime($datetime);
    $now = time();
    $diff = $now - $ts;

    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M j, H:i', $ts);
}

function get_markdown_safe(string $text): string
{
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
    $text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $text);
    $text = preg_replace('/`(.+?)`/', '<code>$1</code>', $text);
    $text = preg_replace('/\n/', '<br>', $text);
    return $text;
}
