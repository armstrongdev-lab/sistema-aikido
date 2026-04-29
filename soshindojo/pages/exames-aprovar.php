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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aluno_id'])) {
    $alunoId = intval($_POST['aluno_id']);
    $resalvas = isset($_POST['resalvas']) ? intval($_POST['resalvas']) : 0;
    $situacao = 'aprovado'; // padrão

    $acaoGraduarAmarela = false;

    if (isset($_POST['situacao'])) {
        if ($_POST['situacao'] === 'reprovado') {
            $situacao = 'reprovado';
        } elseif ($_POST['situacao'] === 'graduar_amarela') {
            $situacao = 'aprovado'; // gravação correta no ENUM
            $acaoGraduarAmarela = true; // flag interna
        }
    }


    try {
        // Buscar graduação atual e dojo_id do aluno
        $stmt = $pdo->prepare("SELECT graduacao, dojo_id FROM alunos WHERE id = ?");
        $stmt->execute([$alunoId]);
        $aluno = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($aluno) {
            $graduacaoAtual = intval($aluno['graduacao']);
            $dojoId = intval($aluno['dojo_id']);

            // Inserir registro do exame com dojo_id
            $stmt = $pdo->prepare("
                INSERT INTO exames (aluno_id, data_exame, id_faixa, resalvas, situacao, dojo_id)
                VALUES (?, CURDATE(), ?, ?, ?, ?)
            ");
            $stmt->execute([$alunoId, $graduacaoAtual, $resalvas, $situacao, $dojoId]);

            if ($situacao === 'aprovado') {
                if ($acaoGraduarAmarela) {
                    // Aqui defina o ID da faixa amarela — EXEMPLO:
                    $idFaixaAmarela = 3;

                    // Atualizar direto para faixa amarela (pulando faixa laranja)
                    $novaGraduacao = $idFaixaAmarela;
                } else {
                    // Buscar próxima faixa normalmente
                    $stmt = $pdo->prepare("SELECT id FROM faixas WHERE id > ? ORDER BY id ASC LIMIT 1");
                    $stmt->execute([$graduacaoAtual]);
                    $proximaFaixa = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($proximaFaixa) {
                        $novaGraduacao = $proximaFaixa['id'];
                    } else {
                        $novaGraduacao = null; // faixa máxima
                    }
                }

                if ($novaGraduacao !== null) {
                    // Atualizar aluno com nova faixa e data_inicio atual
                    $stmt = $pdo->prepare("UPDATE alunos SET graduacao = ?, data_inicio = CURDATE() WHERE id = ?");
                    $stmt->execute([$novaGraduacao, $alunoId]);

                    // Inserir nova contagem para a nova faixa
                    $stmt = $pdo->prepare("SELECT faixa_cor FROM faixas WHERE id = ?");
                    $stmt->execute([$novaGraduacao]);
                    $faixa = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($faixa) {
                        $faixaCor = $faixa['faixa_cor'];

                        $stmt = $pdo->prepare("
                            INSERT INTO faixas_treinos (aluno_id, numero_treinos, data_inicio, id_faixa, faixa)
                            VALUES (?, 0, CURDATE(), ?, ?)
                        ");
                        $stmt->execute([$alunoId, $novaGraduacao, $faixaCor]);

                        $_SESSION['mensagem'] = "Aluno aprovado! Faixa atualizada para {$faixaCor}, contagem reiniciada.";
                    } else {
                        $_SESSION['mensagem'] = "Faixa não encontrada para id {$novaGraduacao}. Exame registrado.";
                    }
                } else {
                    $_SESSION['mensagem'] = "Este aluno já está na faixa máxima. Exame registrado.";
                }
            } else {
                $_SESSION['mensagem'] = "Aluno reprovado. Exame registrado.";
            }
        } else {
            $_SESSION['mensagem'] = "Aluno não encontrado.";
        }
    } catch (PDOException $e) {
        $_SESSION['mensagem'] = "Erro ao processar exame: " . $e->getMessage();
    }

    header('Location: exames-gerenciar.php');
    exit;
} else {
    header('Location: exames-gerenciar.php');
    exit;
}
