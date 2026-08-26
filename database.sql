-- ============================================================
-- BANCO DE DADOS: Sistema de Ativação IPTV por MAC & Key
-- ============================================================

CREATE DATABASE IF NOT EXISTS `iptv_panel` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `iptv_panel`;

-- 1. Tabela de Administradores
CREATE TABLE IF NOT EXISTS `admins` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `email` VARCHAR(100) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Tabela de Dispositivos / Clientes
CREATE TABLE IF NOT EXISTS `devices` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `mac_address` VARCHAR(32) NOT NULL UNIQUE,
    `device_key` VARCHAR(16) NOT NULL,
    `customer_name` VARCHAR(100) NULL DEFAULT 'Cliente Novo',
    `customer_phone` VARCHAR(30) NULL,
    `playlist_url` TEXT NULL,
    `playlist_type` ENUM('m3u', 'xtream') DEFAULT 'm3u',
    `status` ENUM('pending', 'active', 'inactive', 'expired') DEFAULT 'pending',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `expires_at` DATETIME NULL,
    `last_connected_at` DATETIME NULL,
    `app_version` VARCHAR(20) NULL,
    `notes` TEXT NULL,
    INDEX `idx_mac` (`mac_address`),
    INDEX `idx_status` (`status`),
    INDEX `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Inserir Usuário Administrador Padrão (Login: admin | Senha: admin123)
-- Hash gerado com password_hash('admin123', PASSWORD_BCRYPT)
INSERT INTO `admins` (`username`, `password_hash`, `email`)
VALUES ('admin', '$2y$10$e/WLEDvLrgSjOTyHkMo48OWmeZxQkaaMaD1iDuqkXaXeVkqISDuFW', 'admin@seuservidor.com')
ON DUPLICATE KEY UPDATE `id`=`id`;
