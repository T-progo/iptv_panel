<?php
require_once __DIR__ . '/auth.php';
requireAdminAuth();

$pdo = getDBConnection();

// Atualizar status de expirados automaticamente
$pdo->query("UPDATE devices SET status = 'expired' WHERE expires_at IS NOT NULL AND expires_at < NOW() AND status = 'active'");

// Estatísticas
$totalDevices = $pdo->query("SELECT COUNT(*) FROM devices")->fetchColumn();
$activeDevices = $pdo->query("SELECT COUNT(*) FROM devices WHERE status = 'active'")->fetchColumn();
$pendingDevices = $pdo->query("SELECT COUNT(*) FROM devices WHERE status = 'pending'")->fetchColumn();
$expiredDevices = $pdo->query("SELECT COUNT(*) FROM devices WHERE status = 'expired'")->fetchColumn();

// Buscar todos os dispositivos
$stmt = $pdo->query("SELECT * FROM devices ORDER BY id DESC");
$devices = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Ativação IPTV - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #0b0f19;
            color: #e2e8f0;
            min-height: 100vh;
        }
        .navbar {
            background: rgba(15, 23, 42, 0.8) !important;
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }
        .stat-card {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 20px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 24px -10px rgba(0,0,0,0.5);
        }
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        .content-card {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            overflow: hidden;
        }
        .table {
            color: #e2e8f0;
            margin-bottom: 0;
            vertical-align: middle;
        }
        .table thead th {
            background: rgba(15, 23, 42, 0.8);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            color: #94a3b8;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 14px 16px;
        }
        .table tbody td {
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            padding: 14px 16px;
            background: transparent;
        }
        .badge-mac {
            font-family: 'Courier New', monospace;
            background: rgba(99, 102, 241, 0.15);
            color: #a5b4fc;
            border: 1px solid rgba(99, 102, 241, 0.3);
            border-radius: 6px;
            padding: 4px 8px;
            font-size: 13px;
        }
        .badge-key {
            font-family: 'Courier New', monospace;
            background: rgba(168, 85, 247, 0.15);
            color: #d8b4fe;
            border: 1px solid rgba(168, 85, 247, 0.3);
            border-radius: 6px;
            padding: 4px 8px;
            font-size: 13px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border: none;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
        }
        .form-control, .form-select {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
            border-radius: 10px;
        }
        .form-control:focus, .form-select:focus {
            background: rgba(15, 23, 42, 0.9);
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.25);
            color: #fff;
        }
        .modal-content {
            background: #1e293b;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 18px;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg sticky-top px-3">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center text-white fw-bold" href="#">
            <span class="p-2 bg-primary rounded-3 me-2 d-inline-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                <i class="fa-solid fa-tv"></i>
            </span>
            IPTV Activation Panel
        </a>
        <div class="d-flex align-items-center gap-3">
            <span class="text-secondary small d-none d-md-inline">
                <i class="fa-solid fa-circle-user me-1 text-primary"></i> <?= htmlspecialchars($_SESSION['admin_user']) ?>
            </span>
            <a href="logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-3">
                <i class="fa-solid fa-power-off me-1"></i> Sair
            </a>
        </div>
    </div>
</nav>

<div class="container-fluid px-md-4 py-4">

    <!-- Header & Novo Botão -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold text-white mb-1">Dispositivos & Clientes</h3>
            <p class="text-secondary small mb-0">Gerencie e ative os aplicativos Android TV por MAC e Key</p>
        </div>
        <button class="btn btn-primary rounded-pill px-4 py-2" onclick="openAddModal()">
            <i class="fa-solid fa-plus me-2"></i> Ativar Novo Dispositivo
        </button>
    </div>

    <!-- Cards de Estatísticas -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-secondary small fw-semibold">Total Geral</div>
                        <h2 class="fw-bold text-white mb-0 mt-1"><?= $totalDevices ?></h2>
                    </div>
                    <div class="stat-icon bg-secondary bg-opacity-25 text-light">
                        <i class="fa-solid fa-layer-group"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-secondary small fw-semibold">Ativos</div>
                        <h2 class="fw-bold text-success mb-0 mt-1"><?= $activeDevices ?></h2>
                    </div>
                    <div class="stat-icon bg-success bg-opacity-25 text-success">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-secondary small fw-semibold">Pendentes (Novos)</div>
                        <h2 class="fw-bold text-warning mb-0 mt-1"><?= $pendingDevices ?></h2>
                    </div>
                    <div class="stat-icon bg-warning bg-opacity-25 text-warning">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-secondary small fw-semibold">Expirados</div>
                        <h2 class="fw-bold text-danger mb-0 mt-1"><?= $expiredDevices ?></h2>
                    </div>
                    <div class="stat-icon bg-danger bg-opacity-25 text-danger">
                        <i class="fa-solid fa-ban"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela & Filtros -->
    <div class="content-card">
        <div class="p-3 border-bottom border-secondary border-opacity-25 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <!-- Filtros de Status -->
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-outline-secondary active" onclick="filterTable('all', this)">Todos</button>
                <button type="button" class="btn btn-outline-secondary" onclick="filterTable('pending', this)">
                    Pendentes <span class="badge bg-warning text-dark ms-1"><?= $pendingDevices ?></span>
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="filterTable('active', this)">Ativos</button>
                <button type="button" class="btn btn-outline-secondary" onclick="filterTable('expired', this)">Expirados</button>
            </div>

            <!-- Busca -->
            <div class="input-group" style="max-width: 320px;">
                <span class="input-group-text bg-dark border-secondary border-opacity-25 text-secondary">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
                <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Buscar por MAC, Nome ou Telefone..." onkeyup="searchTable()">
            </div>
        </div>

        <div class="table-responsive">
            <table class="table" id="devicesTable">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>MAC Address</th>
                        <th>Device Key</th>
                        <th>Lista M3U</th>
                        <th>Validade / Status</th>
                        <th>Último Acesso</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($devices)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-secondary">
                                <i class="fa-solid fa-tv fa-2x mb-3 d-block opacity-50"></i>
                                Nenhum dispositivo cadastrado ainda.<br>
                                Quando o app abrir na TV pela primeira vez, ele aparecerá aqui automaticamente!
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($devices as $d): ?>
                            <?php 
                                $isExp = ($d['expires_at'] && strtotime($d['expires_at']) < time());
                                $rowStatus = $isExp ? 'expired' : $d['status'];
                                $hasM3u = !empty($d['playlist_url']);
                            ?>
                            <tr data-status="<?= $rowStatus ?>" data-search="<?= strtolower($d['customer_name'] . ' ' . $d['mac_address'] . ' ' . $d['customer_phone']) ?>">
                                <td>
                                    <div class="fw-bold text-white"><?= htmlspecialchars($d['customer_name'] ?: 'Cliente Sem Nome') ?></div>
                                    <?php if (!empty($d['customer_phone'])): ?>
                                        <div class="text-secondary small"><i class="fa-brands fa-whatsapp text-success me-1"></i><?= htmlspecialchars($d['customer_phone']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge-mac copyable" onclick="copyText('<?= $d['mac_address'] ?>')" title="Clique para copiar">
                                        <?= htmlspecialchars($d['mac_address']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge-key copyable" onclick="copyText('<?= $d['device_key'] ?>')" title="Clique para copiar">
                                        <?= htmlspecialchars($d['device_key']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($hasM3u): ?>
                                        <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25">
                                            <i class="fa-solid fa-check me-1"></i> M3U Vinculada
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-25">
                                            <i class="fa-solid fa-triangle-exclamation me-1"></i> Sem Lista
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($rowStatus === 'active'): ?>
                                        <span class="badge bg-success mb-1">Ativo</span>
                                        <div class="small text-secondary">
                                            Vence: <?= $d['expires_at'] ? date('d/m/Y', strtotime($d['expires_at'])) : 'Vitalício' ?>
                                        </div>
                                    <?php elseif ($rowStatus === 'pending'): ?>
                                        <span class="badge bg-warning text-dark mb-1">Pendente</span>
                                        <div class="small text-secondary">Aguardando M3U</div>
                                    <?php elseif ($rowStatus === 'expired'): ?>
                                        <span class="badge bg-danger mb-1">Expirado</span>
                                        <div class="small text-secondary">Venceu em <?= date('d/m/Y', strtotime($d['expires_at'])) ?></div>
                                    <?php else: ?>
                                        <span class="badge bg-secondary mb-1">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-secondary">
                                    <?= $d['last_connected_at'] ? date('d/m H:i', strtotime($d['last_connected_at'])) : 'Nunca' ?>
                                    <?php if ($d['app_version']): ?>
                                        <span class="badge bg-dark text-secondary">v<?= htmlspecialchars($d['app_version']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <!-- Ativar / Editar -->
                                        <button class="btn btn-outline-primary" onclick="openEditModal(<?= $d['id'] ?>)" title="Editar / Configurar M3U">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>

                                        <!-- Adicionar +30 dias rápido -->
                                        <button class="btn btn-outline-success" onclick="extendDays(<?= $d['id'] ?>, 30)" title="Renovar +30 Dias">
                                            <i class="fa-solid fa-calendar-plus"></i> +30d
                                        </button>

                                        <!-- Copiar WhatsApp -->
                                        <button class="btn btn-outline-info" onclick="copyWhatsAppMessage('<?= htmlspecialchars($d['customer_name']) ?>', '<?= $d['mac_address'] ?>', '<?= $d['device_key'] ?>', '<?= $d['expires_at'] ? date('d/m/Y', strtotime($d['expires_at'])) : '30 dias' ?>')" title="Copiar Mensagem WhatsApp">
                                            <i class="fa-brands fa-whatsapp"></i>
                                        </button>

                                        <!-- Excluir -->
                                        <button class="btn btn-outline-danger" onclick="deleteDevice(<?= $d['id'] ?>)" title="Excluir">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Adicionar / Editar Dispositivo -->
<div class="modal fade" id="deviceModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-secondary border-opacity-25">
                <h5 class="modal-title text-white" id="modalTitle">Ativar Dispositivo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="deviceForm" onsubmit="saveDevice(event)">
                <input type="hidden" name="id" id="deviceId" value="0">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-light">MAC Address *</label>
                            <input type="text" name="mac_address" id="macAddress" class="form-control" placeholder="00:1A:79:AB:CD:EF" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-light">Device Key *</label>
                            <input type="text" name="device_key" id="deviceKey" class="form-control" placeholder="849201" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-light">Nome do Cliente</label>
                            <input type="text" name="customer_name" id="customerName" class="form-control" placeholder="Ex: João da Silva">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-light">WhatsApp / Telefone</label>
                            <input type="text" name="customer_phone" id="customerPhone" class="form-control" placeholder="Ex: (11) 99999-9999">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-light">URL da Playlist M3U / M3U8 *</label>
                            <textarea name="playlist_url" id="playlistUrl" rows="2" class="form-control" placeholder="http://servidor.com:80/get.php?username=...&password=...&type=m3u_plus"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-light">Status</label>
                            <select name="status" id="deviceStatus" class="form-select">
                                <option value="active">Ativo (Liberado)</option>
                                <option value="pending">Pendente</option>
                                <option value="inactive">Inativo (Bloqueado)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-light">Data de Expiração</label>
                            <input type="datetime-local" name="expires_at" id="expiresAt" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-light">Anotações / Observações</label>
                            <input type="text" name="notes" id="notes" class="form-control" placeholder="Ex: Plano Mensal R$ 35,00">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary border-opacity-25">
                    <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Salvar & Ativar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const deviceModal = new bootstrap.Modal(document.getElementById('deviceModal'));

function openAddModal() {
    document.getElementById('deviceForm').reset();
    document.getElementById('deviceId').value = '0';
    document.getElementById('modalTitle').innerText = 'Ativar Novo Dispositivo';
    // Definir data padrão de +30 dias
    const date = new Date();
    date.setDate(date.getDate() + 30);
    document.getElementById('expiresAt').value = date.toISOString().slice(0, 16);
    deviceModal.show();
}

function openEditModal(id) {
    fetch(`api_actions.php?action=get_device&id=${id}`)
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') {
                const d = res.data;
                document.getElementById('deviceId').value = d.id;
                document.getElementById('macAddress').value = d.mac_address;
                document.getElementById('deviceKey').value = d.device_key;
                document.getElementById('customerName').value = d.customer_name || '';
                document.getElementById('customerPhone').value = d.customer_phone || '';
                document.getElementById('playlistUrl').value = d.playlist_url || '';
                document.getElementById('deviceStatus').value = d.status;
                document.getElementById('notes').value = d.notes || '';
                if (d.expires_at) {
                    document.getElementById('expiresAt').value = d.expires_at.replace(' ', 'T').slice(0, 16);
                } else {
                    document.getElementById('expiresAt').value = '';
                }
                document.getElementById('modalTitle').innerText = 'Editar Dispositivo & M3U';
                deviceModal.show();
            } else {
                Swal.fire('Erro', res.message, 'error');
            }
        });
}

function saveDevice(e) {
    e.preventDefault();
    const formData = new FormData(document.getElementById('deviceForm'));
    formData.append('action', 'save_device');

    fetch('api_actions.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') {
                Swal.fire({ icon: 'success', title: 'Sucesso!', text: res.message, timer: 1500, showConfirmButton: false })
                    .then(() => location.reload());
            } else {
                Swal.fire('Erro', res.message, 'error');
            }
        });
}

function extendDays(id, days) {
    Swal.fire({
        title: `Adicionar +${days} dias?`,
        text: "A validade do cliente será estendida automaticamente.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sim, estender',
        cancelButtonText: 'Cancelar'
    }).then(result => {
        if (result.isConfirmed) {
            const fd = new FormData();
            fd.append('action', 'extend_days');
            fd.append('id', id);
            fd.append('days', days);

            fetch('api_actions.php', { method: 'POST', body: fd })
                .then(res => res.json())
                .then(res => {
                    if (res.status === 'success') {
                        Swal.fire({ icon: 'success', title: 'Renovado!', text: res.message, timer: 1800, showConfirmButton: false })
                            .then(() => location.reload());
                    } else {
                        Swal.fire('Erro', res.message, 'error');
                    }
                });
        }
    });
}

function deleteDevice(id) {
    Swal.fire({
        title: 'Excluir Dispositivo?',
        text: 'O cliente perderá o acesso imediatamente.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Sim, excluir',
        cancelButtonText: 'Cancelar'
    }).then(result => {
        if (result.isConfirmed) {
            const fd = new FormData();
            fd.append('action', 'delete_device');
            fd.append('id', id);

            fetch('api_actions.php', { method: 'POST', body: fd })
                .then(res => res.json())
                .then(res => {
                    if (res.status === 'success') {
                        location.reload();
                    } else {
                        Swal.fire('Erro', res.message, 'error');
                    }
                });
        }
    });
}

function copyText(text) {
    navigator.clipboard.writeText(text);
    Swal.fire({ icon: 'success', title: 'Copiado!', text: text, timer: 1000, showConfirmButton: false });
}

function copyWhatsAppMessage(name, mac, key, validade) {
    const msg = `Olá, ${name || 'Cliente'}! 🎉\n\nSeu aplicativo de TV foi ativado com sucesso!\n\n📺 *Dados da sua Conta:*\n• *MAC:* ${mac}\n• *Device Key:* ${key}\n• *Validade:* ${validade}\n\nAbra o aplicativo na sua TV para aproveitar todos os canais e conteúdos! Bom entretenimento! 🍿✨`;
    navigator.clipboard.writeText(msg);
    Swal.fire({
        icon: 'success',
        title: 'Mensagem Copiada! 💬',
        text: 'Cole direto no WhatsApp do cliente.',
        timer: 2000,
        showConfirmButton: false
    });
}

function filterTable(status, btn) {
    document.querySelectorAll('.btn-group .btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    const rows = document.querySelectorAll('#devicesTable tbody tr');
    rows.forEach(row => {
        const rowStatus = row.getAttribute('data-status');
        if (status === 'all' || rowStatus === status) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function searchTable() {
    const term = document.getElementById('searchInput').value.toLowerCase();
    const rows = document.querySelectorAll('#devicesTable tbody tr');
    rows.forEach(row => {
        const searchData = row.getAttribute('data-search') || '';
        if (searchData.includes(term)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>
</body>
</html>
