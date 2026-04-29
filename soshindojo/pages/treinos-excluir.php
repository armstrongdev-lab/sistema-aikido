<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'sensei') {
    header('Location: ../index.php');
    exit;
}

require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['treino_id'])) {
    $treinoId = (int) $_POST['treino_id'];

    // Recupera o dojo_id do sensei logado
    $stmt = $pdo->prepare("SELECT dojo_id FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $dojo_id = $stmt->fetchColumn();

    if (!$dojo_id) {
        die("Dojo não encontrado para o sensei.");
    }

    // Verifica se o treino pertence ao dojo do sensei
    $stmt = $pdo->prepare("SELECT id FROM treinos_horarios WHERE id = ? AND dojo_id = ?");
    $stmt->execute([$treinoId, $dojo_id]);
    $treino = $stmt->fetch();

    if ($treino) {
        // Exclui o treino
        $stmt = $pdo->prepare("DELETE FROM treinos_horarios WHERE id = ?");
        $stmt->execute([$treinoId]);
    }

    header("Location: treinos-cadastrar.php");
    exit;

} else {
    header("Location: treinos-cadastrar.php");
    exit;
}
