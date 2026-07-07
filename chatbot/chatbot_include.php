<?php
/**
 * chatbot.php - Main entry point for the chatbot
 * Include this file in any page to add the chatbot.
 * Place right before </body> tag.
 */

require_once __DIR__ . '/config.php';

// Prevent duplicate loading
if (defined('CHATBOT_LOADED')) return;
define('CHATBOT_LOADED', true);

// Initialize session
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
if (empty($_SESSION['chatbot_session_id'])) {
    $_SESSION['chatbot_session_id'] = bin2hex(random_bytes(32));
}
?>
<!-- Nas Digital AI Chatbot -->
<link rel="stylesheet" href="chatbot/assets/chatbot.css">
<script src="chatbot/assets/chatbot.js" defer></script>
<!-- End Nas Digital AI Chatbot -->
