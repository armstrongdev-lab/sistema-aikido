<?php
require_once '../includes/db.php';
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'] ?? '';
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $endereco = $_POST['endereco'] ?? '';
    $dojo = $_POST['dojo'] ?? '';
    $data_nascimento = $_POST['data_nascimento'] ?? '';
    $data_inicio = $_POST['data_inicio'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'E-mail inválido.']);
        exit;
    }

    if (strlen($senha) < 6) {
        echo json_encode(['success' => false, 'message' => 'A senha deve ter pelo menos 6 caracteres.']);
        exit;
    }

    try {
        // REMOVIDO: verificação de e-mail duplicado
// $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ?");
// $stmtCheck->execute([$email]);
// if ($stmtCheck->fetchColumn() > 0) {
//     echo json_encode(['success' => false, 'message' => 'E-mail já cadastrado.']);
//     exit;
// }

        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
        $pdo->beginTransaction();

        $dojo_id = $_POST['dojo_id'] ?? null;
        $status = 'pendente';

        $stmtUser = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, tipo_usuario, dojo_id, status) VALUES (?, ?, ?, 'aluno', ?, ?)");
        $stmtUser->execute([$nome, $email, $senhaHash, $dojo_id, $status]);


        $usuario_id = $pdo->lastInsertId();

        $dojo_id = $_POST['dojo_id'] ?? null;

        $stmtAluno = $pdo->prepare("INSERT INTO alunos (usuario_id, telefone, endereco, dojo_id, data_nascimento, data_inicio, data_inicio_treinos, graduacao) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
        $stmtAluno->execute([$usuario_id, $telefone, $endereco, $dojo_id, $data_nascimento, $data_inicio, $data_inicio]); // o mesmo valor usado duas vezes

        $aluno_id = $pdo->lastInsertId();

        // Buscar id e nome da faixa 'Branca'
        $stmtBuscaFaixa = $pdo->prepare("SELECT id, faixa_cor FROM faixas WHERE faixa_cor = ?");
        $stmtBuscaFaixa->execute(['Branca']);
        $faixa = $stmtBuscaFaixa->fetch(PDO::FETCH_ASSOC);

        if (!$faixa || !isset($faixa['id'], $faixa['faixa_cor'])) {
            throw new Exception("Faixa 'Branca' não encontrada.");
        }

        $id_faixa = $faixa['id'];
        $faixa_nome = $faixa['faixa_cor'];

        // Inserir na tabela faixas_treinos com campo 'faixa' (varchar)
        $stmtFaixa = $pdo->prepare("INSERT INTO faixas_treinos (aluno_id, id_faixa, faixa, numero_treinos, data_inicio) VALUES (?, ?, ?, 0, CURDATE())");
        $stmtFaixa->execute([$aluno_id, $id_faixa, $faixa_nome]);


        $pdo->commit();

        // Decide o redirecionamento com base na origem
        $redirect = 'login.php';
        if (
            isset($_POST['from']) && $_POST['from'] === 'sensei' &&
            isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'sensei'
        ) {
            $redirect = '/aikido/pages/painel-sensei.php';
        }

        echo json_encode(['success' => true, 'redirect' => $redirect]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Erro ao cadastrar: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método inválido.']);
}
