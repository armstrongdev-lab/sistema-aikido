<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id']) || 
    ($_SESSION['tipo_usuario'] !== 'sensei' && $_SESSION['tipo_usuario'] !== 'admin')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'Acesso negado']);
    exit;
}

$usuarioId = $_SESSION['usuario_id'];
$senhaInformada = $_POST['senha_adm'] ?? '';

// Buscar senha_adm do usuário
$stmt = $pdo->prepare("SELECT senha_adm FROM usuarios WHERE id = ?");
$stmt->execute([$usuarioId]);
$senhaHash = $stmt->fetchColumn();

if ($senhaHash && password_verify($senhaInformada, $senhaHash)) {
    $_SESSION['senha_adm_valida'] = true;
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'msg' => 'Senha incorreta']);
}