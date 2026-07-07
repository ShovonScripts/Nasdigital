<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/database.php';

class VisitorManager
{
    private PDO $pdo;
    private string $sessionId;
    private ?array $visitorData = null;

    public function __construct(string $sessionId)
    {
        $this->pdo = Database::getConnection();
        $this->sessionId = $sessionId;
    }

    public function getOrCreate(array $data = []): array
    {
        $existing = $this->findBySession();
        if ($existing) {
            $this->visitorData = $existing;
            $this->updateLastVisit();
            return $existing;
        }

        return $this->create($data);
    }

    public function getVisitor(): ?array
    {
        if ($this->visitorData === null) {
            $this->visitorData = $this->findBySession();
        }
        return $this->visitorData;
    }

    public function getVisitorId(): ?int
    {
        $v = $this->getVisitor();
        return $v ? (int)$v['id'] : null;
    }

    public function incrementConversations(): void
    {
        $id = $this->getVisitorId();
        if ($id) {
            $stmt = $this->pdo->prepare(
                'UPDATE visitors SET total_conversations = total_conversations + 1 WHERE id = ?'
            );
            $stmt->execute([$id]);
        }
    }

    public function incrementMessages(): void
    {
        $id = $this->getVisitorId();
        if ($id) {
            $stmt = $this->pdo->prepare(
                'UPDATE visitors SET total_messages = total_messages + 1 WHERE id = ?'
            );
            $stmt->execute([$id]);
        }
    }

    private function findBySession(): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM visitors WHERE session_id = ? LIMIT 1');
        $stmt->execute([$this->sessionId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    private function create(array $data): array
    {
        $ip = $data['ip'] ?? get_ip();
        $ua = $data['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? '');
        $geo = get_geo($ip);

        $stmt = $this->pdo->prepare("
            INSERT INTO visitors (
                session_id, name, email, phone, ip_address, country, city,
                browser, device, os, language, screen_resolution, referrer, user_agent
            ) VALUES (
                :session_id, :name, :email, :phone, :ip, :country, :city,
                :browser, :device, :os, :language, :screen_resolution, :referrer, :user_agent
            )
        ");

        $stmt->execute([
            'session_id' => $this->sessionId,
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'ip' => $ip,
            'country' => $geo['country'],
            'city' => $geo['city'],
            'browser' => $data['browser'] ?? get_browser_name($ua),
            'device' => $data['device'] ?? get_device($ua),
            'os' => $data['os'] ?? get_os($ua),
            'language' => $data['language'] ?? ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''),
            'screen_resolution' => $data['screen_resolution'] ?? null,
            'referrer' => $data['referrer'] ?? ($_SERVER['HTTP_REFERER'] ?? ''),
            'user_agent' => $ua,
        ]);

        $id = (int)$this->pdo->lastInsertId();
        $this->visitorData = $this->findBySession();

        return $this->visitorData ?? [];
    }

    private function updateLastVisit(): void
    {
        $id = $this->getVisitorId();
        if ($id) {
            $stmt = $this->pdo->prepare(
                'UPDATE visitors SET last_visit = NOW() WHERE id = ?'
            );
            $stmt->execute([$id]);
        }
    }
}
