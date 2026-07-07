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

if (!$input) {
    error_response('Invalid request');
}

$sessionId = $input['session_id'] ?? get_or_create_session();
$latestMessage = $input['latest_message'] ?? '';
$name = $input['name'] ?? 'Anonymous';
$callerName = $input['caller_name'] ?? $name;
$telegram = $input['telegram'] ?? '';
$currentPage = $input['current_page'] ?? ($_SERVER['HTTP_REFERER'] ?? '');

Database::createTables();

$chatbot = new Chatbot($sessionId);
$chatbot->init([
    'name' => $name,
    'current_page' => $currentPage,
]);

$result = $chatbot->requestHuman($latestMessage, [
    'current_page' => $currentPage,
    'name' => $callerName,
    'caller_name' => $callerName,
    'telegram' => $telegram,
]);

json_response($result);
