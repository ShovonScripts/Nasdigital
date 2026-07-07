<?php

class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            try {
                self::$instance = new PDO(
                    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                    DB_USER,
                    DB_PASS,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]
                );
            } catch (PDOException $e) {
                error_log('Database connection failed: ' . $e->getMessage());
                http_response_code(500);
                echo json_encode(['error' => 'Database connection failed']);
                exit;
            }
        }
        return self::$instance;
    }

    public static function createTables(): void
    {
        $pdo = self::getConnection();

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `visitors` (
                `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `session_id` VARCHAR(64) NOT NULL UNIQUE,
                `name` VARCHAR(255) DEFAULT NULL,
                `email` VARCHAR(255) DEFAULT NULL,
                `phone` VARCHAR(50) DEFAULT NULL,
                `ip_address` VARCHAR(45) DEFAULT NULL,
                `country` VARCHAR(100) DEFAULT NULL,
                `city` VARCHAR(100) DEFAULT NULL,
                `browser` VARCHAR(255) DEFAULT NULL,
                `device` VARCHAR(100) DEFAULT NULL,
                `os` VARCHAR(100) DEFAULT NULL,
                `language` VARCHAR(50) DEFAULT NULL,
                `screen_resolution` VARCHAR(20) DEFAULT NULL,
                `referrer` TEXT DEFAULT NULL,
                `user_agent` TEXT DEFAULT NULL,
                `first_visit` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `last_visit` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `total_conversations` INT UNSIGNED DEFAULT 0,
                `total_messages` INT UNSIGNED DEFAULT 0,
                INDEX `idx_session` (`session_id`),
                INDEX `idx_ip` (`ip_address`),
                INDEX `idx_last_visit` (`last_visit`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `conversations` (
                `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `visitor_id` BIGINT UNSIGNED NOT NULL,
                `session_id` VARCHAR(64) NOT NULL,
                `status` ENUM('active', 'waiting', 'resolved', 'human_requested') DEFAULT 'active',
                `current_page` TEXT DEFAULT NULL,
                `page_title` VARCHAR(255) DEFAULT NULL,
                `message_count` INT UNSIGNED DEFAULT 0,
                `started_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `ended_at` DATETIME DEFAULT NULL,
                `human_requested_at` DATETIME DEFAULT NULL,
                `ip_address` VARCHAR(45) DEFAULT NULL,
                `country` VARCHAR(100) DEFAULT NULL,
                `city` VARCHAR(100) DEFAULT NULL,
                `device` VARCHAR(100) DEFAULT NULL,
                `browser` VARCHAR(255) DEFAULT NULL,
                FOREIGN KEY (`visitor_id`) REFERENCES `visitors`(`id`) ON DELETE CASCADE,
                INDEX `idx_session` (`session_id`),
                INDEX `idx_visitor` (`visitor_id`),
                INDEX `idx_status` (`status`),
                INDEX `idx_started` (`started_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `messages` (
                `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `conversation_id` BIGINT UNSIGNED NOT NULL,
                `visitor_id` BIGINT UNSIGNED NOT NULL,
                `session_id` VARCHAR(64) NOT NULL,
                `role` ENUM('user', 'assistant', 'system', 'human') NOT NULL DEFAULT 'user',
                `message` TEXT NOT NULL,
                `message_type` VARCHAR(50) DEFAULT 'text',
                `ai_model` VARCHAR(100) DEFAULT NULL,
                `ai_latency_ms` INT UNSIGNED DEFAULT NULL,
                `telegram_sent` TINYINT(1) DEFAULT 0,
                `created_at` DATETIME(3) DEFAULT CURRENT_TIMESTAMP(3),
                FOREIGN KEY (`conversation_id`) REFERENCES `conversations`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`visitor_id`) REFERENCES `visitors`(`id`) ON DELETE CASCADE,
                INDEX `idx_conversation` (`conversation_id`),
                INDEX `idx_session` (`session_id`),
                INDEX `idx_created` (`created_at`),
                INDEX `idx_role` (`role`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `human_requests` (
                `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `visitor_id` BIGINT UNSIGNED NOT NULL,
                `conversation_id` BIGINT UNSIGNED NOT NULL,
                `session_id` VARCHAR(64) NOT NULL,
                `status` ENUM('pending', 'contacted', 'resolved') DEFAULT 'pending',
                `latest_message` TEXT DEFAULT NULL,
                `current_page` TEXT DEFAULT NULL,
                `caller_name` VARCHAR(255) DEFAULT NULL,
                `telegram` VARCHAR(255) DEFAULT NULL,
                `requested_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `contacted_at` DATETIME DEFAULT NULL,
                `telegram_sent` TINYINT(1) DEFAULT 0,
                FOREIGN KEY (`visitor_id`) REFERENCES `visitors`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`conversation_id`) REFERENCES `conversations`(`id`) ON DELETE CASCADE,
                INDEX `idx_status` (`status`),
                INDEX `idx_session` (`session_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Add columns if upgrading from older version
        try { $pdo->exec("ALTER TABLE `human_requests` ADD COLUMN `caller_name` VARCHAR(255) DEFAULT NULL AFTER `current_page`"); } catch (PDOException $e) {}
        try { $pdo->exec("ALTER TABLE `human_requests` ADD COLUMN `telegram` VARCHAR(255) DEFAULT NULL AFTER `caller_name`"); } catch (PDOException $e) {}
    }
}
