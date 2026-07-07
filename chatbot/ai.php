<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/knowledge.php';

class GroqAI
{
    private string $apiKey;
    private string $model;
    private KnowledgeBase $knowledge;

    public function __construct()
    {
        $this->apiKey = GROQ_API_KEY;
        $this->model = GROQ_MODEL;
        $this->knowledge = new KnowledgeBase();
    }

    public function generateResponse(string $message, array $history = []): array
    {
        $startTime = microtime(true);

        $systemPrompt = $this->buildSystemPrompt();

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        foreach ($history as $msg) {
            $role = ($msg['role'] ?? '') === 'user' ? 'user' : 'assistant';
            $messages[] = ['role' => $role, 'content' => $msg['message'] ?? ''];
        }

        $messages[] = ['role' => 'user', 'content' => $message];

        $payload = [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => 0.7,
            'max_tokens' => 1024,
            'top_p' => 0.9,
            'frequency_penalty' => 0.3,
            'presence_penalty' => 0.3,
        ];

        $result = $this->callAPI($payload);
        $latency = round((microtime(true) - $startTime) * 1000);

        return [
            'response' => $result['response'],
            'model' => $this->model,
            'latency_ms' => $latency,
            'tokens' => $result['tokens'] ?? 0,
            'confidence' => $result['confidence'] ?? 'high',
        ];
    }

    private function buildSystemPrompt(): string
    {
        $context = $this->knowledge->getContextString();

        return <<<PROMPT
You are the official AI Assistant of Mr. Nas (Nasir Uddin).
Your name is "Mr. Nas AI Assistant".

Your role is to help visitors understand Mr. Nas, his businesses, services, training programs, and digital ecosystem.

PERSONALITY:
- Professional, friendly, motivational, and helpful
- Business-minded and confident
- Warm and approachable, never robotic
- Use natural, conversational English
- Keep responses concise but informative (2-4 paragraphs max)

RULES:
1. ONLY answer based on the knowledge provided below.
2. If the question is not covered in your knowledge, respond EXACTLY:
"I'm not completely sure about that. I've notified Mr. Nas's team, and someone will contact you shortly."
3. Never invent information or make claims about services not listed.
4. Always be polite and professional.
5. When mentioning websites, include the full URL.
6. Encourage visitors to reach out via Telegram for specific inquiries.

KNOWLEDGE BASE:
{$context}

Always end your response with a relevant follow-up question or invitation to ask more.
PROMPT;
    }

    private function callAPI(array $payload): array
    {
        $ch = curl_init(GROQ_API_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'NasDigital-Chatbot/1.0',
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            log_error('Groq API cURL error', ['error' => $curlError]);
            return [
                'response' => "I'm not completely sure about that. I've notified Mr. Nas's team, and someone will contact you shortly.",
                'confidence' => 'low',
            ];
        }

        $data = json_decode($response, true);

        if ($httpCode !== 200 || !$data) {
            log_error('Groq API error', ['http_code' => $httpCode, 'response' => substr($response ?? '', 0, 500)]);
            return [
                'response' => "I'm not completely sure about that. I've notified Mr. Nas's team, and someone will contact you shortly.",
                'confidence' => 'low',
            ];
        }

        $text = $data['choices'][0]['message']['content'] ?? '';
        $tokens = $data['usage']['total_tokens'] ?? 0;

        if (empty($text)) {
            return [
                'response' => "I'm not completely sure about that. I've notified Mr. Nas's team, and someone will contact you shortly.",
                'confidence' => 'low',
            ];
        }

        return [
            'response' => trim($text),
            'tokens' => $tokens,
            'confidence' => 'high',
        ];
    }
}
