<?php
/**
 * Conexão segura com o Banco de Dados MySQL via PDO
 */

// Configurações do Banco de Dados
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'iptv_panel');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

function getDBConnection() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

            // Auto-migração: garante que as tabelas necessárias existam
            ensureTablesExist($pdo);

        } catch (PDOException $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'Erro de conexão com o banco de dados: ' . $e->getMessage()
            ]);
            exit;
        }
    }
    return $pdo;
}

function ensureTablesExist($pdo) {
    try {
        $check = $pdo->query("SHOW TABLES LIKE 'admins'")->rowCount();
        if ($check === 0) {
            // Tabela admins não existe -> cria tabelas padrão
            $sqlAdmins = "CREATE TABLE IF NOT EXISTS `admins` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `username` VARCHAR(50) NOT NULL UNIQUE,
                `password_hash` VARCHAR(255) NOT NULL,
                `email` VARCHAR(100) NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

            $sqlDevices = "CREATE TABLE IF NOT EXISTS `devices` (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

            $pdo->exec($sqlAdmins);
            $pdo->exec($sqlDevices);
        }

        // Garante que o usuário admin existe e tem a senha admin123 correta
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = 'admin'");
        $stmt->execute();
        $admin = $stmt->fetch();

        $hash = password_hash('admin123', PASSWORD_BCRYPT);
        if (!$admin) {
            $stmtInsert = $pdo->prepare("INSERT INTO admins (username, password_hash, email) VALUES ('admin', :hash, 'admin@seuservidor.com')");
            $stmtInsert->execute([':hash' => $hash]);
        } else if (!password_verify('admin123', $admin['password_hash'])) {
            $stmtUpdate = $pdo->prepare("UPDATE admins SET password_hash = :hash WHERE username = 'admin'");
            $stmtUpdate->execute([':hash' => $hash]);
        }
    } catch (Exception $ex) {
        // Ignora se der erro pontual
    }
}
