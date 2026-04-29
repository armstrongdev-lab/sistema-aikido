<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../includes/db.php';

// Permitir acesso para sensei ou admin
if (!in_array($_SESSION['tipo_usuario'], ['sensei', 'admin'])) {
    header('Location: ../index.php');
    exit;
}

// Definir dojo_id conforme tipo de usuário
$tipo_usuario = $_SESSION['tipo_usuario'];

if ($tipo_usuario === 'sensei') {
    // Sensei usa dojo da sessão
    $dojo_id = $_SESSION['dojo_id'] ?? null;
    if (!$dojo_id) {
        die("Erro: dojo_id não encontrado na sessão.");
    }
} else {
    // Admin pode enviar dojo via POST (filtro)
    // Se não enviar, considera null (todos dojos)
    $dojo_id = isset($_POST['dojo_id']) && $_POST['dojo_id'] !== '' ? (int) $_POST['dojo_id'] : null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data_presenca = $_POST['data_presenca'] ?? null;
    $treino_id = $_POST['treino_id'] ?? null;
    $alunos_presentes = $_POST['alunos'] ?? [];

    if (!$data_presenca) {
        die('Data da presença é obrigatória.');
    }
    if (!$treino_id) {
        die('Selecione um treino.');
    }
    if (empty($alunos_presentes)) {
        die('Nenhum aluno foi selecionado para marcar presença.');
    }

    // Verifica se treino_id existe no banco
    $stmtTreino = $pdo->prepare("SELECT id FROM treinos_horarios WHERE id = ?");
    $stmtTreino->execute([$treino_id]);
    $treinoExiste = $stmtTreino->fetchColumn();

    if (!$treinoExiste) {
        die('Treino selecionado inválido.');
    }

    try {
        $pdo->beginTransaction();

        $status_presente = 'presente';

        // Preparar query de inserção (dojo_id pode ser null)
        $stmtInserir = $pdo->prepare("INSERT INTO presencas (aluno_id, data_presenca, status, treino_id, dojo_id) VALUES (?, ?, ?, ?, ?)");

        $stmtVerifica = $pdo->prepare("SELECT COUNT(*) FROM presencas WHERE aluno_id = ? AND data_presenca = ? AND treino_id = ?");
        $stmtFaixa = $pdo->prepare("SELECT id FROM faixas_treinos WHERE aluno_id = ? ORDER BY data_inicio DESC LIMIT 1");
        $stmtUpdate = $pdo->prepare("UPDATE faixas_treinos SET numero_treinos = numero_treinos + 0 WHERE id = ?");

        $presencasInseridas = 0;

        foreach ($alunos_presentes as $aluno_id) {
            // Verificar se já existe presença para este aluno nesta data e treino
            $stmtVerifica->execute([$aluno_id, $data_presenca, $treino_id]);
            $jaExiste = $stmtVerifica->fetchColumn();

            if ($jaExiste == 0) {
                // Inserir presença com dojo_id (pode ser null)
                $stmtInserir->execute([$aluno_id, $data_presenca, $status_presente, $treino_id, $dojo_id]);
                $presencasInseridas++;

                // Buscar a faixa_treino mais recente do aluno
                $stmtFaixa->execute([$aluno_id]);
                $faixaTreino = $stmtFaixa->fetch();

                if ($faixaTreino) {
                    $faixaTreinoId = $faixaTreino['id'];
                    // Atualizar número de treinos
                    $stmtUpdate->execute([$faixaTreinoId]);
                }
            }
        }

        $pdo->commit();

        header("Location: presencas-marcar.php?sucesso=1&inseridas=$presencasInseridas");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Erro ao registrar presenças: " . $e->getMessage());
    }
} else {
    die("Método inválido.");
}
