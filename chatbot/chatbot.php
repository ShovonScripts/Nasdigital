<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/visitor.php';
require_once __DIR__ . '/ai.php';
require_once __DIR__ . '/telegram.php';

class Chatbot
{
    private PDO $pdo;
    private string $sessionId;
    private VisitorManager $visitor;
    private GroqAI $ai;
    private ?int $visitorId = null;
    private ?int $conversationId = null;

    public function __construct(string $sessionId)
    {
        $this->pdo = Database::getConnection();
        $this->sessionId = $sessionId;
        $this->visitor = new VisitorManager($sessionId);
        $this->ai = new GroqAI();
    }

    public function init(array $visitorData = []): array
    {
        $visitor = $this->visitor->getOrCreate($visitorData);
        $this->visitorId = (int)$visitor['id'];

        $conversation = $this->getActiveConversation();
        if (!$conversation) {
            $conversation = $this->createConversation($visitorData);
            $this->conversationId = (int)$conversation['id'];

            ChatTelegram::sendNewChatNotification(
                $this->sessionId,
                $visitorData['current_page'] ?? ($_SERVER['HTTP_REFERER'] ?? 'https://nasdigital.uk'),
                $visitor['name'] ?? 'Anonymous'
            );

            $this->visitor->incrementConversations();
        } else {
            $this->conversationId = (int)$conversation['id'];
            $this->updateConversationPage($visitorData['current_page'] ?? null);
        }

        return $this->getConversationData();
    }

    public function processMessage(string $message, array $context = []): array
    {
        $this->ensureInitialized();

        $message = sanitize_message($message);
        if (empty($message)) {
            return ['success' => false, 'error' => 'Message cannot be empty'];
        }

        if (!check_rate_limit($this->sessionId)) {
            return ['success' => false, 'error' => 'Too many requests. Please wait a moment.'];
        }

        $this->saveMessage('user', $message);

        $history = $this->getConversationHistory();

        $aiResult = $this->ai->generateResponse($message, $history);

        $this->saveMessage('assistant', $aiResult['response'], [
            'ai_model' => $aiResult['model'],
            'ai_latency_ms' => $aiResult['latency_ms'],
        ]);

        $this->updateConversationAfterMessage();

        ChatTelegram::sendVisitorMessage(
            $message,
            $context['current_page'] ?? ($_SERVER['HTTP_REFERER'] ?? 'https://nasdigital.uk'),
            $context['name'] ?? 'Anonymous',
            $this->sessionId
        );

        if ($aiResult['confidence'] === 'low') {
            ChatTelegram::sendLowConfidence(
                $message,
                $context['current_page'] ?? ($_SERVER['HTTP_REFERER'] ?? 'https://nasdigital.uk'),
                $this->sessionId
            );
        }

        return [
            'success' => true,
            'message' => $aiResult['response'],
            'conversation_id' => $this->conversationId,
            'timestamp' => date('Y-m-d H:i:s'),
        ];
    }

    public function requestHuman(string $latestMessage, array $context = []): array
    {
        $this->ensureInitialized();

        $stmt = $this->pdo->prepare("
            INSERT INTO human_requests (visitor_id, conversation_id, session_id, latest_message, current_page, caller_name, telegram)
            VALUES (:visitor_id, :conversation_id, :session_id, :latest_message, :current_page, :caller_name, :telegram)
        ");
        $stmt->execute([
            'visitor_id' => $this->visitorId,
            'conversation_id' => $this->conversationId,
            'session_id' => $this->sessionId,
            'latest_message' => $latestMessage,
            'current_page' => $context['current_page'] ?? null,
            'caller_name' => $context['caller_name'] ?? null,
            'telegram' => $context['telegram'] ?? null,
        ]);

        $stmt = $this->pdo->prepare("
            UPDATE conversations SET status = 'human_requested', human_requested_at = NOW() WHERE id = ?
        ");
        $stmt->execute([$this->conversationId]);

        ChatTelegram::sendHumanRequest(
            $context['current_page'] ?? ($_SERVER['HTTP_REFERER'] ?? 'https://nasdigital.uk'),
            $latestMessage,
            $this->sessionId,
            $context['name'] ?? ($context['caller_name'] ?? 'Anonymous'),
            $context['telegram'] ?? null
        );

        return [
            'success' => true,
            'message' => 'Our team has received your request. Mr. Nas or a team member will contact you soon.',
        ];
    }

    public function getHistory(): array
    {
        $this->ensureInitialized();

        $stmt = $this->pdo->prepare("
            SELECT role, message, created_at
            FROM messages
            WHERE conversation_id = ?
            ORDER BY created_at ASC
            LIMIT " . MAX_CONVERSATION_HISTORY . "
        ");
        $stmt->execute([$this->conversationId]);
        return $stmt->fetchAll();
    }

    public function getSuggestedQuestions(): array
    {
        return [
            'Who is Mr. Nas?',
            'Tell me about Nas Digital.',
            'What is NasHub?',
            'How can you help my business?',
            'Can you build my website?',
            'How do I contact Mr. Nas?',
            'Can I schedule a consultation?',
        ];
    }

    private function ensureInitialized(): void
    {
        if ($this->visitorId === null || $this->conversationId === null) {
            $this->init();
        }
    }

    private function getActiveConversation(): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM conversations
            WHERE session_id = ? AND status IN ('active', 'human_requested')
            ORDER BY updated_at DESC
            LIMIT 1
        ");
        $stmt->execute([$this->sessionId]);
        return $stmt->fetch() ?: null;
    }

    private function createConversation(array $data): array
    {
        $ip = $data['ip'] ?? get_ip();
        $ua = $data['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? '');
        $geo = get_geo($ip);

        $stmt = $this->pdo->prepare("
            INSERT INTO conversations (
                visitor_id, session_id, current_page, page_title,
                ip_address, country, city, device, browser
            ) VALUES (
                :visitor_id, :session_id, :current_page, :page_title,
                :ip, :country, :city, :device, :browser
            )
        ");

        $stmt->execute([
            'visitor_id' => $this->visitorId,
            'session_id' => $this->sessionId,
            'current_page' => $data['current_page'] ?? null,
            'page_title' => $data['page_title'] ?? 'Nas Digital',
            'ip' => $ip,
            'country' => $geo['country'],
            'city' => $geo['city'],
            'device' => get_device($ua),
            'browser' => get_browser_name($ua),
        ]);

        $id = (int)$this->pdo->lastInsertId();
        return ['id' => $id];
    }

    private function updateConversationPage(?string $page): void
    {
        if ($page) {
            $stmt = $this->pdo->prepare(
                'UPDATE conversations SET current_page = ? WHERE id = ?'
            );
            $stmt->execute([$page, $this->conversationId]);
        }
    }

    private function updateConversationAfterMessage(): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE conversations
            SET message_count = message_count + 1, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$this->conversationId]);
        $this->visitor->incrementMessages();
    }

    private function saveMessage(string $role, string $message, array $extra = []): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO messages (
                conversation_id, visitor_id, session_id, role, message,
                ai_model, ai_latency_ms
            ) VALUES (
                :conversation_id, :visitor_id, :session_id, :role, :message,
                :ai_model, :ai_latency_ms
            )
        ");

        $stmt->execute([
            'conversation_id' => $this->conversationId,
            'visitor_id' => $this->visitorId,
            'session_id' => $this->sessionId,
            'role' => $role,
            'message' => $message,
            'ai_model' => $extra['ai_model'] ?? null,
            'ai_latency_ms' => $extra['ai_latency_ms'] ?? null,
        ]);
    }

    private function getConversationHistory(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT role, message FROM messages
            WHERE conversation_id = ?
            ORDER BY created_at ASC
            LIMIT " . MAX_CONVERSATION_HISTORY . "
        ");
        $stmt->execute([$this->conversationId]);
        return $stmt->fetchAll();
    }

    private function getConversationData(): array
    {
        $visitor = $this->visitor->getVisitor();
        return [
            'success' => true,
            'session_id' => $this->sessionId,
            'visitor' => $visitor,
            'conversation_id' => $this->conversationId,
            'suggested_questions' => $this->getSuggestedQuestions(),
        ];
    }
}
