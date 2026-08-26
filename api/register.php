<?php
/**
 * API: Registro Automático de Novos Dispositivos (Chamada quando o app abre)
 */

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

require_once __DIR__ . '/../config/database.php';

// Obter parâmetros via GET ou POST JSON
$mac = $_GET['mac'] ?? '';
$key = $_GET['key'] ?? '';
$version = $_GET['version'] ?? '1.0';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input) {
        $mac = $input['mac'] ?? $mac;
        $key = $input['key'] ?? $key;
        $version = $input['version'] ?? $version;
    }
}

// Limpar e formatar MAC (maiúsculo, remover espaços)
$mac = strtoupper(trim($mac));
$key = trim($key);

if (empty($mac) || empty($key)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Parâmetros "mac" e "key" são obrigatórios.'
    ]);
    exit;
}

$pdo = getDBConnection();

try {
    // 1. Verificar se o dispositivo já existe
    $stmt = $pdo->prepare("SELECT * FROM devices WHERE mac_address = :mac LIMIT 1");
    $stmt->execute([':mac' => $mac]);
    $device = $stmt->fetch();

    if ($device) {
        // Atualizar última conexão e versão
        $update = $pdo->prepare("
            UPDATE devices 
            SET last_connected_at = NOW(), 
                app_version = :version,
                device_key = :key
            WHERE id = :id
        ");
        $update->execute([
            ':version' => $version,
            ':key' => $key,
            ':id' => $device['id']
        ]);

        echo json_encode([
            'status' => 'success',
            'message' => 'Dispositivo já cadastrado.',
            'device_status' => $device['status'],
            'expires_at' => $device['expires_at']
        ]);
    } else {
        // Inserir novo dispositivo com status 'pending'
        $insert = $pdo->prepare("
            INSERT INTO devices (mac_address, device_key, status, app_version, last_connected_at, customer_name)
            VALUES (:mac, :key, 'pending', :version, NOW(), 'Novo Dispositivo')
        ");
        $insert->execute([
            ':mac' => $mac,
            ':key' => $key,
            ':version' => $version
        ]);

        echo json_encode([
            'status' => 'success',
            'message' => 'Dispositivo registrado com sucesso e aguardando ativação.',
            'device_status' => 'pending',
            'expires_at' => null
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Erro interno ao registrar dispositivo: ' . $e->getMessage()
    ]);
}
