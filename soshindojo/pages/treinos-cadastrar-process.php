<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php'); // ajuste o caminho se necessário
    exit;
}

require_once '../includes/db.php';

if ($_SESSION['tipo_usuario'] !== 'sensei') {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dias = $_POST['dias'] ?? [];
    $horarios = $_POST['horarios'] ?? [];

    if (empty($dias) || empty($horarios)) {
    header('Location: treinos-cadastrar.php?erro=selecionar_dia_horario');
    exit;
}


    try {
        $pdo->beginTransaction();

        // Recupera o dojo_id do sensei logado
        $stmtDojo = $pdo->prepare("SELECT dojo_id FROM usuarios WHERE id = ?");
        $stmtDojo->execute([$_SESSION['usuario_id']]);
        $dojo_id = $stmtDojo->fetchColumn();

        if (!$dojo_id) {
            throw new Exception("Dojo não encontrado para o sensei.");
        }

        // Prepara inserção com dojo_id
        $stmt = $pdo->prepare("INSERT INTO treinos_horarios (dia_semana, horario, dojo_id) VALUES (?, ?, ?)");

        $stmtVerifica = $pdo->prepare("SELECT COUNT(*) FROM treinos_horarios WHERE dia_semana = ? AND horario = ? AND dojo_id = ?");
        $stmtInsere = $pdo->prepare("INSERT INTO treinos_horarios (dia_semana, horario, dojo_id) VALUES (?, ?, ?)");

        foreach ($dias as $dia) {
            foreach ($horarios as $hora) {
                $horarioCompleto = $hora . ':00';

                // Verifica se já existe
                $stmtVerifica->execute([$dia, $horarioCompleto, $dojo_id]);
                if ($stmtVerifica->fetchColumn() == 0) {
                    // Se não existe, insere
                    $stmtInsere->execute([$dia, $horarioCompleto, $dojo_id]);
                }
            }
        }


        $pdo->commit();

        header('Location: treinos-cadastrar.php?msg=treinos_cadastrados');
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Erro ao cadastrar horários: " . $e->getMessage();
    }
} else {
    echo "Método inválido.";
}
