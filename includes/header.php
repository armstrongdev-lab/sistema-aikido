<?php
if (session_status() === PHP_SESSION_NONE) {session_start();
}
require_once __DIR__ . '/../includes/db.php';

$currentPage = basename($_SERVER['PHP_SELF']);
$baseUrl = '/';
$cssPath = $baseUrl . 'assets/css/style.css';

$projectRoot = realpath(__DIR__ . '/..'); // raiz do projeto no servidor

// Padrões
$logoPath = 'assets/img/logo-padrao.png';
$faviconPath = 'assets/img/favicon-padrao.ico';
$nomeDojo = 'Soshin Dojo';

if (isset($_SESSION['usuario_id'])) {
    try {
        $usuarioId = $_SESSION['usuario_id'];

        // Buscar dojo_id do usuário
        $stmt = $pdo->prepare("SELECT dojo_id FROM usuarios WHERE id = ?");
        $stmt->execute([$usuarioId]);
        $usuario = $stmt->fetch();

        if ($usuario) {
            $dojoId = $usuario['dojo_id'];

            // Buscar nome do dojo
            $stmt = $pdo->prepare("SELECT nome FROM dojos WHERE id = ?");
            $stmt->execute([$dojoId]);
            $dojo = $stmt->fetch();

            if ($dojo) {
                $nomeDojo = htmlspecialchars($dojo['nome']);

                // Caminhos relativos à raiz do projeto (sem "../")
                $dojoLogoRelativePath = "assets/img/logos/dojo$dojoId.png";
                $dojoFaviconRelativePath = "assets/img/logos/favicon-dojo$dojoId.ico";

                // Verifica se os arquivos existem no sistema (caminho absoluto)
                if (file_exists($projectRoot . '/' . $dojoLogoRelativePath)) {
                    $logoPath = $dojoLogoRelativePath;
                }
                if (file_exists($projectRoot . '/' . $dojoFaviconRelativePath)) {
                    $faviconPath = $dojoFaviconRelativePath;
                }
            }
        }
    } catch (PDOException $e) {
        // Erro silencioso, mantém valores padrão
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title><?= $nomeDojo ?></title>

    <link rel="shortcut icon" href="<?= $baseUrl . $faviconPath ?>" type="image/x-icon">
    <link rel="stylesheet" href="<?= $cssPath ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        /* Header fixo apenas para páginas públicas */
body:not(.logado) .main-header {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    z-index: 1000;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
}

/* Espaço no topo quando header é fixo */
body:not(.logado) {
    padding-top: 71px;
}

        .main-header {
            background-color: #222;
            color: #fff;
            padding: 1rem;
            position: relative;
        }

        .main-header h1 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .main-header h1 .logo {
            height: 40px;
            width: auto;
            display: block;
        }

        nav ul {
            list-style: none;
            display: flex;
            gap: 1rem;
            padding: 0;
            margin: 0;
        }

        nav ul li a {
            color: #fff;
            text-decoration: none;
            display: block;              /* 🔹 faz o link ocupar toda a linha */
           /* padding: 0.75rem 1rem;        🔹 aumenta a área de clique */
            width: 100%;
        }

        /* Por padrão, botão hamburguer não aparece */
        .menu-toggle {
            display: none;
            font-size: 2rem;
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            position: absolute;
            top: 1rem;
            right: 1rem;
            z-index: 1000;
        }

        /* Menu padrão: horizontal */
        nav ul {
            display: flex;
            flex-direction: row;
            gap: 2rem;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        /* ATÉ 1100px: ativa o hamburguer, esconde menu padrão */
        @media (max-width: 1100px) {
            .menu-toggle {
                display: block;
            }

            nav ul {
                display: none;
                flex-direction: column;
                background-color: #333;
                position: absolute;
                top: 60px;
                left: 0;
                width: 100%;
                padding: 1rem;
                z-index: 999;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            }

            nav ul.active {
                display: flex;
            }
        }
    </style>
</head>

<body class="<?= isset($_SESSION['usuario_id']) ? 'logado' : '' ?>">

    <header class="main-header">
        <div class="container">
            <h1><img src="<?= $baseUrl . $logoPath ?>" alt="Logo <?= htmlspecialchars($nomeDojo) ?>" class="logo">
                <?= htmlspecialchars($nomeDojo) ?></h1>

            <button class="menu-toggle" id="menu-toggle">☰</button>

            <?php if (isset($_SESSION['usuario_id'])): ?>
                <!-- Menu logado -->
                <nav>
                    <ul id="menu">
                        <?php if ($_SESSION['tipo_usuario'] === 'sensei' || $_SESSION['tipo_usuario'] === 'admin'): ?>
                            <li><a href="../pages/painel-sensei.php">Painel Sensei</a></li>
                            <li><a href="../pages/painel-aluno.php">Painel Aluno</a></li>
                            <li><a href="../pages/presencas.php">Presenças</a></li>
                            <li><a href="../pages/presencas-marcar.php">Marcar Presença</a></li>
                            <li><a href="../pages/alunos-listar.php">Ver Alunos</a></li>
                        <!--<li><a href="/aikido/pages/mensalidades-lancar.php">Lançar Mensalidades</a></li> -->
                            <li><a href="../pages/register.php">Cadastrar Aluno</a></li>
                            <li><a href="../pages/exames-gerenciar.php">Exames</a></li>
                            <li><a href="treinos-cadastrar.php">Cadastrar Treinos</a></li>
                        <?php else: ?>
                            <li><a href="../pages/painel-aluno.php">Painel Aluno</a></li>
                            <li><a href="../pages/mensalidades.php">Mensalidades</a></li>
                            <li><a href="../pages/presencas.php">Presenças</a></li>
                        <?php endif; ?>
                        <li><a href="../logout.php">Sair</a></li>
                    </ul>
                </nav>
            <?php else: ?>
                <!-- Menu público -->
                <nav>
                    <ul id="menu">
                        <li><a href="<?= $baseUrl ?>index.php">Início</a></li>
                        <li><a href="<?= $baseUrl ?>pages/sobre.php">Sobre</a></li>
                        <li><a href="<?= $baseUrl ?>pages/historia-aikido.php">História</a></li>
                        <li><a href="<?= $baseUrl ?>pages/galeria.php">Galeria</a></li>
                        <li><a href="<?= $baseUrl ?>pages/horarios.php">Horários</a></li>
                        <li><a href="<?= $baseUrl ?>pages/endereco.php">Endereço</a></li>
                        <li><a href="<?= $baseUrl ?>pages/login.php">Login</a></li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </header>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toggle = document.getElementById('menu-toggle');
            const menu = document.getElementById('menu');
            toggle.addEventListener('click', function () {
                menu.classList.toggle('active');
            });
        });
    </script>

</body>

</html>