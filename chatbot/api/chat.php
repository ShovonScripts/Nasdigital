<?php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../chatbot.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_response('Method not allowed', 405);
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['message'])) {
    error_response('Message is required');
}

$sessionId = $input['session_id'] ?? get_or_create_session();
$message = $input['message'];
$name = $input['name'] ?? 'Anonymous';
$currentPage = $input['current_page'] ?? ($_SERVER['HTTP_REFERER'] ?? '');
$pageTitle = $input['page_title'] ?? '';

Database::createTables();

$chatbot = new Chatbot($sessionId);

$visitorData = [
    'name' => $name,
    'current_page' => $currentPage,
    'page_title' => $pageTitle,
    'ip' => get_ip(),
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
    'browser' => get_browser_name($_SERVER['HTTP_USER_AGENT'] ?? ''),
    'device' => get_device($_SERVER['HTTP_USER_AGENT'] ?? ''),
    'os' => get_os($_SERVER['HTTP_USER_AGENT'] ?? ''),
    'screen_resolution' => $input['screen_resolution'] ?? null,
    'language' => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
];

$chatbot->init($visitorData);

$result = $chatbot->processMessage($message, [
    'current_page' => $currentPage,
    'name' => $name,
]);

json_response($result);
