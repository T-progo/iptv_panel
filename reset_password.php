<?php
require_once __DIR__ . '/config/database.php';
$pdo = getDBConnection();
$hash = password_hash('admin123', PASSWORD_BCRYPT);
$stmt = $pdo->prepare("UPDATE admins SET password_hash = :hash WHERE username = 'admin'");
$stmt->execute([':hash' => $hash]);
echo "Senha de admin redefinida com sucesso para 'admin123'!\n";
