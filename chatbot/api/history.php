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

Database::createTables();

$sessionId = $_GET['session_id'] ?? ($_POST['session_id'] ?? get_or_create_session());

$chatbot = new Chatbot($sessionId);

$input = json_decode(file_get_contents('php://input'), true);
if ($input && !empty($input['session_id'])) {
    $sessionId = $input['session_id'];
    $chatbot = new Chatbot($sessionId);
}

$chatbot->init();

$history = $chatbot->getHistory();
$suggested = $chatbot->getSuggestedQuestions();

json_response([
    'success' => true,
    'session_id' => $sessionId,
    'history' => $history,
    'suggested_questions' => $suggested,
]);
