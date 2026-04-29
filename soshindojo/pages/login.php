<?php
session_start();

// Redireciona se já estiver logado
if (isset($_SESSION['usuario_id']) && isset($_SESSION['tipo_usuario'])) {
    if ($_SESSION['tipo_usuario'] === 'sensei') {
        header('Location: ../pages/painel-sensei.php');
    } else {
        header('Location: ../pages/painel-aluno.php');
    }
    exit;
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>


<style>
    body {
        background: #f0f0f0;
    }

    .login-container {
        max-width: 360px;
        margin: 80px auto;
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgb(0 0 0 / 0.1);
        text-align: center;
    }

    .login-logo {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        object-fit: cover;
        margin-bottom: 20px;
    }

    .login-container input[type="email"],
    .login-container input[type="password"] {
        width: 100%;
        padding: 12px;
        margin-bottom: 0px;
        border: 1px solid #ccc;
        border-radius: 5px;
        box-sizing: border-box;
    }

    .login-container button {
        width: 100%;
        background-color: #000;
        color: white;
        padding: 12px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 1rem;
    }

    .login-container button:hover {
        background-color: #222;
    }

    .login-container p {
        margin-top: 5px;
    }

    .login-container a {
        color: #000000;
        text-decoration: none;
    }

    .login-container a:hover {
        text-decoration: underline;
    }

    .mensagem-erro {
        background-color: #ffdddd;
        color: #a94442;
        padding: 12px;
        border: 1px solid #f5c6cb;
        border-radius: 5px;
        margin-bottom: 20px;
        font-size: 0.95rem;
        text-align: center;
    }
</style>

<div class="login-container">

    <img src="../assets/img/logo.png" alt="Logo Aikido" class="login-logo">

    <h2>Login</h2>

    <?php if (isset($_GET['erro'])): ?>
        <div class="mensagem-erro">
            <?php
            switch ($_GET['erro']) {
                case 1:
                    echo "E-mail ou senha incorretos.";
                    break;
                case 2:
                    echo "Método de requisição inválido.";
                    break;
                case 3:
                    echo "Seu cadastro ainda está em análise. Aguarde aprovação do sensei.";
                    break;
                case 4:
                    echo "Seu acesso foi bloqueado. Entre em contato com o sensei.";
                    break;
                case 5:
                    echo "Mensalidade vencida. Regularize o pagamento com o sensei.";
                    break;
            }
            ?>
        </div>
    <?php endif; ?>

    <form action="login-process.php" method="POST">
        <input type="email" name="email" placeholder="E-mail" required>
        <input type="password" name="senha" placeholder="Senha" required>
        <p><a href="esqueci-senha.php"><strong>Esqueci minha senha</strong></a></p>
        <button type="submit">Entrar</button>
    </form>

    <p>Não tem uma conta? <a href="register.php"><strong>Cadastre-se</strong></a></p>
</div>

<?php include '../includes/footer.php'; ?>