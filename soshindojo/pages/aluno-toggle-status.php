<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['tipo_usuario'], ['admin', 'sensei'])) {
    header('Location: ../index.php');
    exit;
}

if (!isset($_GET['id']) || !isset($_GET['status'])) {
    echo "Parâmetros inválidos.";
    exit;
}

$usuarioId = (int) $_GET['id'];
$novoStatus = $_GET['status'] == '1' ? 1 : 0;

$stmt = $pdo->prepare("UPDATE usuarios SET ativo = :status WHERE id = :id");
$stmt->execute(['status' => $novoStatus, 'id' => $usuarioId]);

header('Location: alunos-listar.php');
exit;
