<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();


if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['tipo_usuario'], ['admin', 'sensei'])) {
    header("Location: login.php");
    exit;
}

// Verifica se senha_adm foi validada
if (empty($_SESSION['senha_adm_valida'])) {
    header("Location: verificar-senha-adm.php");
    exit;
}

require_once '../includes/db.php';

// Processar ações de aprovação ou rejeição
$acoes_validas = ['aprovar', 'rejeitar'];

if (isset($_GET['acao'], $_GET['id']) && in_array($_GET['acao'], $acoes_validas)) {
    $id = (int) $_GET['id'];
    $status = $_GET['acao'] === 'aprovar' ? 'aprovado' : 'rejeitado';

    $stmt = $pdo->prepare("UPDATE usuarios SET status = ? WHERE id = ? AND tipo_usuario = 'aluno'");
    $stmt->execute([$status, $id]);

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}


// Buscar alunos pendentes com nome do dojo
$sql = "
    SELECT u.id, u.nome, u.email, d.nome AS dojo
    FROM usuarios u
    LEFT JOIN alunos a ON u.id = a.usuario_id
    LEFT JOIN dojos d ON a.dojo_id = d.id
    WHERE u.tipo_usuario = 'aluno' AND u.status = 'pendente'
";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$pendentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Aprovação de Alunos</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <?php include '../includes/header.php'; ?>
    <style>
    .painel-aluno {
    width: 100%;
    overflow-x: auto;
}

/* Estilo padrão da tabela */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

thead {
    background-color: #f2f2f2;
}

th, td {
    padding: 10px;
    border: 1px solid #ddd;
    text-align: left;
}

/* Botões */
.btn-edit, .btn-danger {
    width: 100px; /* mesmo tamanho */
    padding: 8px 0;
    border-radius: 5px;
    color: #fff;
    text-decoration: none;
    text-align: center;
    margin-right: 5px;
    display: inline-block;
    font-weight: bold;
    transition: background-color 0.3s;
}

.btn-edit {
    background-color: #28a745;
}

.btn-edit:hover {
    background-color: #218838;
}

.btn-danger {
    background-color: #dc3545;
}

.btn-danger:hover {
    background-color: #c82333;
}

/* Container para alinhar os botões */
.btn-container {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}


/* === RESPONSIVO: estilo card === */@media (max-width: 768px) {
    table {
        border: 0;
    }

    thead {
        display: none;
    }

    table, tbody, tr, td {
        display: block;
        width: 100%;
    }

    tr {
        background: #f9f9f9;
        margin-bottom: 15px;
        border: 1px solid #ccc;
        border-radius: 8px;
        padding: 10px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    td {
        text-align: left;
        padding-left: 30%;
        position: relative;
        border: none;
        border-bottom: 1px solid #eee;
        word-wrap: break-word;
    }

    td::before {
        content: attr(data-label);
        position: absolute;
        left: 10px;
        top: 10px;
        width: 45%;
        font-weight: bold;
        white-space: nowrap;
    }

    td:last-child {
        border-bottom: none;
    }

    /* Não quebrar Nome e Email, cortar com reticências */
    td[data-label="Nome"],
    td[data-label="Email"] {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
        display: block;
    }
}


</style>

</head>
<body>

<main class="main-content">
    <div class="painel-aluno">
        <h2>Alunos Pendentes de Aprovação</h2>

        <?php if (count($pendentes) === 0): ?>
            <p>Não há alunos pendentes no momento.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Dojo</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendentes as $aluno): ?>
                        <tr>
                            <td data-label="Nome"><?= htmlspecialchars($aluno['nome']) ?></td>
                            <td data-label="Email"><?= htmlspecialchars($aluno['email']) ?></td>
                            <td data-label="Dojo"><?= htmlspecialchars($aluno['dojo'] ?? 'Não informado') ?></td>
                            <td data-label="Ações">
                                <a class="btn-edit" href="?acao=aprovar&id=<?= $aluno['id'] ?>" onclick="return confirm('Deseja APROVAR este aluno?')">✅ Aprovar</a>
                                <a class="btn-danger" href="?acao=rejeitar&id=<?= $aluno['id'] ?>" onclick="return confirm('Deseja REJEITAR este aluno?')">❌ Rejeitar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
</body>
</html>
