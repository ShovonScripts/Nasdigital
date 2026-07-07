<?php

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'nasdigit_chatbot');
define('DB_USER', getenv('DB_USER') ?: 'nasdigit_chatbot');
define('DB_PASS', getenv('DB_PASS') ?: 'chatbot1234');

define('GROQ_API_KEY', getenv('GROQ_API_KEY') ?: '');
define('GROQ_MODEL', getenv('GROQ_MODEL') ?: 'llama-3.3-70b-versatile');
define('GROQ_API_URL', 'https://api.groq.com/openai/v1/chat/completions');

define('TELEGRAM_BOT_TOKEN', getenv('TELEGRAM_BOT_TOKEN') ?: '');
define('TELEGRAM_CHAT_ID', getenv('TELEGRAM_CHAT_ID') ?: '-1004399226073');
define('TELEGRAM_API_URL', 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN . '/sendMessage');

define('CHATBOT_LOG_DIR', __DIR__ . '/logs');
define('CHATBOT_LOG_FILE', CHATBOT_LOG_DIR . '/chatbot-errors.log');

// Auto-create logs directory
if (!is_dir(CHATBOT_LOG_DIR)) {
    @mkdir(CHATBOT_LOG_DIR, 0755, true);
}

define('RATE_LIMIT_WINDOW', 5);
define('RATE_LIMIT_MAX', 10);
define('MAX_MESSAGE_LENGTH', 2000);
define('MAX_CONVERSATION_HISTORY', 50);

define('BOT_NAME', 'Mr. Nas');
define('BOT_TITLE', 'AI Assistant');

define('KNOWLEDGE_DIR', __DIR__ . '/knowledge');
