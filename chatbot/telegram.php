<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

class ChatTelegram
{
    public static function sendNewChatNotification(string $sessionId, string $pageUrl, string $visitorName = 'Anonymous'): bool
    {
        $message = "\xF0\x9F\x92\xAC New Chat Started\n\n";
        $message .= "Visitor:\n" . sanitize_html($visitorName) . "\n\n";
        $message .= "Session:\n<code>" . sanitize_html($sessionId) . "</code>\n\n";
        $message .= "Website:\n" . sanitize_html($pageUrl) . "\n\n";
        $message .= "Time:\n" . date('Y-m-d H:i:s');

        return self::send($message);
    }

    public static function sendVisitorMessage(string $messageText, string $pageUrl, string $visitorName = 'Anonymous', string $sessionId = ''): bool
    {
        $message = "\xF0\x9F\x92\xAC Visitor Message\n\n";
        $message .= "Visitor:\n" . sanitize_html($visitorName) . "\n\n";
        $message .= "Message:\n" . sanitize_html($messageText) . "\n\n";
        if ($sessionId) {
            $message .= "Session:\n<code>" . sanitize_html($sessionId) . "</code>\n\n";
        }
        $message .= "Page:\n" . sanitize_html($pageUrl) . "\n\n";
        $message .= "Time:\n" . date('h:i A');

        return self::send($message);
    }

    public static function sendHumanRequest(string $pageUrl, string $latestMessage, string $sessionId = '', string $visitorName = 'Anonymous', ?string $telegram = null): bool
    {
        $message = "\xF0\x9F\x9A\xA8 Human Support Requested\n\n";
        $message .= "Visitor wants to talk with a human.\n\n";
        $message .= "Name:\n" . sanitize_html($visitorName) . "\n\n";
        if ($telegram) {
            $message .= "Telegram:\n@" . sanitize_html(ltrim($telegram, '@')) . "\n\n";
        }
        if ($sessionId) {
            $message .= "Session:\n<code>" . sanitize_html($sessionId) . "</code>\n\n";
        }
        $message .= "Current Page:\n" . sanitize_html($pageUrl) . "\n\n";
        $message .= "Latest Message:\n" . sanitize_html($latestMessage) . "\n\n";
        $message .= "Time:\n" . date('Y-m-d H:i:s');

        return self::send($message);
    }

    public static function sendLowConfidence(string $question, string $pageUrl, string $sessionId = ''): bool
    {
        $message = "\xE2\x9D\x93 AI Low Confidence\n\n";
        $message .= "The AI could not confidently answer this question:\n\n";
        $message .= "Question:\n" . sanitize_html($question) . "\n\n";
        if ($sessionId) {
            $message .= "Session:\n<code>" . sanitize_html($sessionId) . "</code>\n\n";
        }
        $message .= "Page:\n" . sanitize_html($pageUrl) . "\n\n";
        $message .= "Time:\n" . date('Y-m-d H:i:s');

        return self::send($message);
    }

    private static function send(string $text): bool
    {
        $ch = curl_init(TELEGRAM_API_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'chat_id' => TELEGRAM_CHAT_ID,
                'text' => $text,
                'parse_mode' => 'HTML',
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'NasDigital-ChatBot/1.0',
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($httpCode !== 200) {
            log_error('Telegram send failed', [
                'http_code' => $httpCode,
                'error' => $curlError ?: 'Unknown',
                'response' => substr($response ?? '', 0, 200),
            ]);
            return false;
        }

        return true;
    }
}
