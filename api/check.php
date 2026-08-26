<?php
/**
 * API: Verificação de Status e Liberação da Playlist M3U
 * Chamada pelo aplicativo Android TV para carregar canais
 */

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

require_once __DIR__ . '/../config/database.php';

$mac = $_GET['mac'] ?? '';
$key = $_GET['key'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input) {
        $mac = $input['mac'] ?? $mac;
        $key = $input['key'] ?? $key;
    }
}

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
    // Buscar dispositivo por MAC e Key
    $stmt = $pdo->prepare("
        SELECT id, mac_address, device_key, customer_name, playlist_url, playlist_type, status, expires_at 
        FROM devices 
        WHERE mac_address = :mac AND device_key = :key 
        LIMIT 1
    ");
    $stmt->execute([':mac' => $mac, ':key' => $key]);
    $device = $stmt->fetch();

    if (!$device) {
        echo json_encode([
            'status' => 'unregistered',
            'message' => 'Dispositivo não encontrado ou Device Key inválida.',
            'mac' => $mac
        ]);
        exit;
    }

    // Atualizar ping de última conexão
    $pdo->prepare("UPDATE devices SET last_connected_at = NOW() WHERE id = :id")->execute([':id' => $device['id']]);

    // Verificar expiração
    $now = new DateTime();
    $expiresAt = $device['expires_at'] ? new DateTime($device['expires_at']) : null;
    $isExpired = false;
    $daysRemaining = 0;

    if ($expiresAt) {
        if ($now > $expiresAt) {
            $isExpired = true;
            // Atualizar status no banco para expired se ainda estiver como active
            if ($device['status'] === 'active') {
                $pdo->prepare("UPDATE devices SET status = 'expired' WHERE id = :id")->execute([':id' => $device['id']]);
                $device['status'] = 'expired';
            }
        } else {
            $diff = $now->diff($expiresAt);
            $daysRemaining = (int)$diff->format('%a');
        }
    }

    // Retorno de acordo com o status
    if ($device['status'] === 'active' && !$isExpired) {
        if (empty($device['playlist_url'])) {
            echo json_encode([
                'status' => 'active_no_playlist',
                'customer_name' => $device['customer_name'],
                'message' => 'Dispositivo ativado, mas nenhuma lista M3U foi configurada ainda.',
                'expires_at' => $device['expires_at'],
                'days_remaining' => $daysRemaining
            ]);
        } else {
            echo json_encode([
                'status' => 'active',
                'customer_name' => $device['customer_name'],
                'playlist_url' => $device['playlist_url'],
                'playlist_type' => $device['playlist_type'],
                'expires_at' => $device['expires_at'],
                'days_remaining' => $daysRemaining,
                'message' => 'Dispositivo ativo e autorizado.'
            ]);
        }
    } elseif ($device['status'] === 'expired' || $isExpired) {
        echo json_encode([
            'status' => 'expired',
            'customer_name' => $device['customer_name'],
            'expires_at' => $device['expires_at'],
            'message' => 'Sua assinatura expirou em ' . ($expiresAt ? $expiresAt->format('d/m/Y') : 'Data Indefinida') . '. Entre em contato para renovar.'
        ]);
    } elseif ($device['status'] === 'inactive') {
        echo json_encode([
            'status' => 'inactive',
            'customer_name' => $device['customer_name'],
            'message' => 'Dispositivo desativado pelo administrador.'
        ]);
    } else { // pending
        echo json_encode([
            'status' => 'pending',
            'customer_name' => $device['customer_name'],
            'message' => 'Dispositivo aguardando ativação. Envie seu MAC e Key ao suporte.'
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Erro ao verificar dispositivo: ' . $e->getMessage()
    ]);
}
