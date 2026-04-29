<?php
session_start();
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (empty($email) || empty($senha)) {
        header('Location: login.php?erro=1');
        exit;
    }

    // Buscar todos os usuários com o mesmo e-mail
    $sql = "SELECT id, nome, senha, tipo_usuario, dojo_id, status, ativo FROM usuarios WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $usuario_autenticado = null;

    // Verificar a senha para cada usuário encontrado
    foreach ($usuarios as $usuario) {
        if (password_verify($senha, $usuario['senha'])) {
            $usuario_autenticado = $usuario;
            break;
        }
    }

    if ($usuario_autenticado) {
        // Verifica se o cadastro foi aprovado
        if ($usuario_autenticado['status'] !== 'aprovado') {
            header('Location: login.php?erro=3'); // Cadastro ainda não aprovado
            exit;
        }

        // Verifica se usuário está ativo
        if ($usuario_autenticado['ativo'] == 0) {
            header('Location: login.php?erro=4'); // Usuário bloqueado
            exit;
        }

        // Se for aluno, verifica mensalidade
        if ($usuario_autenticado['tipo_usuario'] === 'aluno') {
            $sqlMens = "
                SELECT 1 FROM mensalidades m
                JOIN alunos a ON m.aluno_id = a.id
                WHERE a.usuario_id = :usuario_id
                  AND m.mes_ano = DATE_FORMAT(CURDATE(), '%Y-%m')
                  AND m.status = 'Em Aberto'
                LIMIT 1
            ";
            $stmtMens = $pdo->prepare($sqlMens);
            $stmtMens->bindParam(':usuario_id', $usuario_autenticado['id']);
            $stmtMens->execute();
            $mensalidadeAberta = $stmtMens->fetchColumn();

            // Bloqueia login se mensalidade em aberto e passou dia 10
            if ($mensalidadeAberta && date('j') > 10) {
                header('Location: login.php?erro=5'); // Mensalidade vencida
                exit;
            }
        }

        // Login autorizado
        $_SESSION['usuario_id'] = $usuario_autenticado['id'];
        $_SESSION['nome'] = $usuario_autenticado['nome'];
        $_SESSION['tipo_usuario'] = $usuario_autenticado['tipo_usuario'];
        $_SESSION['dojo_id'] = $usuario_autenticado['dojo_id'];

        // Redireciona para o painel correto
        if ($usuario_autenticado['tipo_usuario'] === 'sensei') {
            header('Location: ../pages/painel-sensei.php');
        } else {
            header('Location: ../pages/painel-aluno.php');
        }
        exit;

    } else {
        // Nenhum usuário com a senha correta
        header('Location: login.php?erro=1');
        exit;
    }
} else {
    // Acesso direto inválido
    header('Location: login.php?erro=2');
    exit;
}
?>
