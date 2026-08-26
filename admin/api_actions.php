<?php
/**
 * Ações AJAX do Painel Administrativo (CRUD de Dispositivos)
 */

header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/auth.php';
requireAdminAuth();

$pdo = getDBConnection();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_device':
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM devices WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $device = $stmt->fetch();
            if ($device) {
                echo json_encode(['status' => 'success', 'data' => $device]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Dispositivo não encontrado.']);
            }
            break;

        case 'save_device':
            $id = (int)($_POST['id'] ?? 0);
            $mac = strtoupper(trim($_POST['mac_address'] ?? ''));
            $key = trim($_POST['device_key'] ?? '');
            $customerName = trim($_POST['customer_name'] ?? '');
            $customerPhone = trim($_POST['customer_phone'] ?? '');
            $playlistUrl = trim($_POST['playlist_url'] ?? '');
            $playlistType = $_POST['playlist_type'] ?? 'm3u';
            $status = $_POST['status'] ?? 'active';
            $expiresAt = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
            $notes = trim($_POST['notes'] ?? '');

            if (empty($mac) || empty($key)) {
                echo json_encode(['status' => 'error', 'message' => 'MAC Address e Device Key são obrigatórios.']);
                exit;
            }

            if ($id > 0) {
                // Atualizar
                $stmt = $pdo->prepare("
                    UPDATE devices SET 
                        mac_address = :mac,
                        device_key = :key,
                        customer_name = :cname,
                        customer_phone = :cphone,
                        playlist_url = :purl,
                        playlist_type = :ptype,
                        status = :status,
                        expires_at = :expires,
                        notes = :notes
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':mac' => $mac,
                    ':key' => $key,
                    ':cname' => $customerName,
                    ':cphone' => $customerPhone,
                    ':purl' => $playlistUrl,
                    ':ptype' => $playlistType,
                    ':status' => $status,
                    ':expires' => $expiresAt,
                    ':notes' => $notes,
                    ':id' => $id
                ]);
                echo json_encode(['status' => 'success', 'message' => 'Dispositivo atualizado com sucesso!']);
            } else {
                // Inserir Novo
                // Se não informou data de expiração, adiciona 30 dias por padrão
                if (!$expiresAt && $status === 'active') {
                    $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
                }

                $stmt = $pdo->prepare("
                    INSERT INTO devices (mac_address, device_key, customer_name, customer_phone, playlist_url, playlist_type, status, expires_at, notes, created_at)
                    VALUES (:mac, :key, :cname, :cphone, :purl, :ptype, :status, :expires, :notes, NOW())
                    ON DUPLICATE KEY UPDATE 
                        device_key = VALUES(device_key),
                        customer_name = VALUES(customer_name),
                        customer_phone = VALUES(customer_phone),
                        playlist_url = VALUES(playlist_url),
                        playlist_type = VALUES(playlist_type),
                        status = VALUES(status),
                        expires_at = VALUES(expires_at),
                        notes = VALUES(notes)
                ");
                $stmt->execute([
                    ':mac' => $mac,
                    ':key' => $key,
                    ':cname' => $customerName ?: 'Cliente Novo',
                    ':cphone' => $customerPhone,
                    ':purl' => $playlistUrl,
                    ':ptype' => $playlistType,
                    ':status' => $status,
                    ':expires' => $expiresAt,
                    ':notes' => $notes
                ]);
                echo json_encode(['status' => 'success', 'message' => 'Dispositivo cadastrado e ativado com sucesso!']);
            }
            break;

        case 'extend_days':
            $id = (int)($_POST['id'] ?? 0);
            $days = (int)($_POST['days'] ?? 30);

            $stmt = $pdo->prepare("SELECT expires_at FROM devices WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $device = $stmt->fetch();

            if (!$device) {
                echo json_encode(['status' => 'error', 'message' => 'Dispositivo não encontrado.']);
                exit;
            }

            $baseTime = time();
            if ($device['expires_at'] && strtotime($device['expires_at']) > time()) {
                $baseTime = strtotime($device['expires_at']);
            }

            $newExpires = date('Y-m-d 23:59:59', strtotime("+$days days", $baseTime));

            $update = $pdo->prepare("UPDATE devices SET expires_at = :expires, status = 'active' WHERE id = :id");
            $update->execute([':expires' => $newExpires, ':id' => $id]);

            echo json_encode([
                'status' => 'success', 
                'message' => "Adicionado +$days dias! Nova validade: " . date('d/m/Y', strtotime($newExpires)),
                'new_expires' => $newExpires
            ]);
            break;

        case 'toggle_status':
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT status FROM devices WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $dev = $stmt->fetch();

            if ($dev) {
                $newStatus = ($dev['status'] === 'active') ? 'inactive' : 'active';
                $pdo->prepare("UPDATE devices SET status = :status WHERE id = :id")->execute([':status' => $newStatus, ':id' => $id]);
                echo json_encode(['status' => 'success', 'new_status' => $newStatus]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Dispositivo não encontrado.']);
            }
            break;

        case 'delete_device':
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare("DELETE FROM devices WHERE id = :id");
            $stmt->execute([':id' => $id]);
            echo json_encode(['status' => 'success', 'message' => 'Dispositivo excluído com sucesso.']);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Ação inválida.']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Erro: ' . $e->getMessage()]);
}
