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

if (!in_array($_SESSION['tipo_usuario'], ['sensei', 'admin'])) {
    header('Location: ../index.php');
    exit;
}

$isAdmin = $_SESSION['tipo_usuario'] === 'admin';
$isSensei = $_SESSION['tipo_usuario'] === 'sensei';

$dojo_id = $_SESSION['dojo_id'] ?? null;
if (!$dojo_id) {
    echo "Dojo não definido.";
    exit;
}

// Buscar faixas para o select
$faixas = $pdo->query("SELECT id, faixa_cor FROM faixas ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

// Para admin, buscar todos os dojos para o filtro
if ($isAdmin) {
    $dojos = $pdo->query("SELECT id, nome FROM dojos ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
}

// Filtros recebidos via GET
$nomeFiltro = $_GET['nome'] ?? '';
$faixaFiltro = $_GET['faixa'] ?? '';
$ativoFiltro = $_GET['ativo'] ?? ''; // novo filtro

// Filtrar dojo:
// Se admin e filtro dojo informado, usa esse filtro
// Se sensei, força filtro para dojo do sensei
$dojoFiltro = null;
if ($isAdmin && !empty($_GET['dojo'])) {
    $dojoFiltro = (int) $_GET['dojo'];
} elseif ($isSensei) {
    $dojoFiltro = $dojo_id;
}

// Montar SQL com filtros dinâmicos
$sql = "
SELECT 
    a.id AS aluno_id,
    u.id AS usuario_id,
    u.nome,
    u.ativo,
    f.faixa_cor AS faixa_atual,
    ft.numero_treinos,
    f.treinos,
    (
        SELECT e.resalvas 
        FROM exames e
        WHERE e.aluno_id = a.id 
          AND e.id_faixa = (
              SELECT MAX(e2.id_faixa)
              FROM exames e2
              WHERE e2.aluno_id = a.id AND e2.id_faixa < ft.id_faixa
          )
        LIMIT 1
    ) AS resalvas,
    (
        SELECT MAX(e3.data_exame)
        FROM exames e3
        WHERE e3.aluno_id = a.id
    ) AS data_ultimo_exame

FROM alunos a
INNER JOIN usuarios u ON a.usuario_id = u.id
LEFT JOIN (
    SELECT ft1.aluno_id, ft1.id_faixa, ft1.numero_treinos
    FROM faixas_treinos ft1
    INNER JOIN (
        SELECT aluno_id, MAX(data_inicio) AS max_data
        FROM faixas_treinos
        GROUP BY aluno_id
    ) ft2 ON ft1.aluno_id = ft2.aluno_id AND ft1.data_inicio = ft2.max_data
) ft ON a.id = ft.aluno_id
LEFT JOIN faixas f ON ft.id_faixa = f.id
WHERE 1=1
";

$params = [];

if ($dojoFiltro) {
    $sql .= " AND a.dojo_id = :dojo_id ";
    $params['dojo_id'] = $dojoFiltro;
}

if (!empty($nomeFiltro)) {
    $sql .= " AND u.nome LIKE :nome";
    $params['nome'] = "%$nomeFiltro%";
}

if (!empty($faixaFiltro)) {
    $sql .= " AND f.id = :faixa_id";
    $params['faixa_id'] = $faixaFiltro;
}

// Aqui adiciona o filtro por ativo
if ($ativoFiltro === '1') {
    $sql .= " AND u.ativo = 1 ";
} elseif ($ativoFiltro === '0') {
    $sql .= " AND u.ativo = 0 ";
}

$sql .= " ORDER BY u.nome ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include '../includes/header.php'; ?>

<style>
    table {
        width: 100%;
        border-collapse: collapse;
        font-family: Arial, sans-serif;
    }

    th,
    td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
    }

    th {
        background-color: #000000;
        color: white;
    }

    tr:nth-child(even) {
        background-color: #f2f2f2;
    }

    tr:hover {
        background-color: #e2e6ea;
    }

    .filtros {
        margin-bottom: 20px;
    }

    form {
        display: flex;
        flex-direction: row;
        gap: 1rem;
        max-width: 100%;
        margin: auto;
        text-align: center;
    }

    .filtros form {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
    }

    .filtros input[type="text"],
    .filtros select {
        padding: 8px;
        border: 1px solid #ccc;
        border-radius: 6px;
        min-width: 200px;
        font-size: 14px;
        flex: 1;
    }

    .filtros button {
        padding: 8px 16px;
        background-color: #000;
        color: #fff;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        transition: background-color 0.3s;
    }

    .filtros button:hover {
        background-color: #333;
    }

    .btn-limpar {
        padding: 8px 16px;
        background-color: #000;
        color: #fff;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        transition: background-color 0.3s;
        text-decoration: none;
        display: inline-block;
        line-height: normal;
    }

    .btn-limpar:hover {
        background-color: #c0c0c0;
        color: #000;
    }

    @media (max-width: 768px) {
        .filtros form {
            flex-direction: column;
            align-items: stretch;
        }

        .filtros input[type="text"],
        .filtros select,
        .filtros button,
        .btn-limpar {
            width: 100%;
        }
    }

    .tabela-responsiva table {
        width: 100%;
        border-collapse: collapse;
    }

    .tabela-responsiva th,
    .tabela-responsiva td {
        padding: 10px;
        border: 1px solid #ddd;
        text-align: left;
    }

    /* Modo responsivo: transforma em "cartões" em telas pequenas */
    @media (max-width: 768px) {

        .tabela-responsiva table,
        .tabela-responsiva thead,
        .tabela-responsiva tbody,
        .tabela-responsiva th,
        .tabela-responsiva td,
        .tabela-responsiva tr {
            display: block;
            width: 100%;
        }

        .tabela-responsiva thead {
            display: none;
            /* Esconde cabeçalho */
        }

        .tabela-responsiva tr {
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 10px;
            background-color: #f9f9f9;
        }

        .tabela-responsiva td {
            text-align: right;
            position: relative;
            padding-left: 50%;
            border: none;
            border-bottom: 1px solid #eee;
        }

        .tabela-responsiva td::before {
            content: attr(data-label);
            position: absolute;
            left: 10px;
            top: 10px;
            font-weight: bold;
            text-align: left;
            color: #333;
        }
    }
</style>

<div class="container">
    <h2>Lista de Alunos</h2>

    <div class="filtros">
        <form method="GET" action="alunos-listar.php">
            <input type="text" name="nome" placeholder="Filtrar por nome" value="<?= htmlspecialchars($nomeFiltro) ?>">

            <select name="faixa">
                <option value="">Todas as faixas</option>
                <?php foreach ($faixas as $faixa): ?>
                    <option value="<?= $faixa['id'] ?>" <?= ($faixaFiltro == $faixa['id'] ? 'selected' : '') ?>>
                        <?= htmlspecialchars($faixa['faixa_cor']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <?php if ($isAdmin): ?>
                <select name="dojo">
                    <option value="">Todos os dojos</option>
                    <?php foreach ($dojos as $dojo): ?>
                        <option value="<?= $dojo['id'] ?>" <?= ($dojoFiltro == $dojo['id'] ? 'selected' : '') ?>>
                            <?= htmlspecialchars($dojo['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>

            <!-- Novo filtro ativo -->
            <select name="ativo">
                <option value="">Todos os status</option>
                <option value="1" <?= $ativoFiltro === '1' ? 'selected' : '' ?>>Ativo</option>
                <option value="0" <?= $ativoFiltro === '0' ? 'selected' : '' ?>>Inativo</option>
            </select>

            <button type="submit">Filtrar</button>
            <a href="alunos-listar.php" class="btn-limpar">🧹 Limpar Filtros</a>
        </form>
    </div>

    <div class="tabela-responsiva">
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Faixa Atual</th>
                    <th>Último Exame</th>
                    <th>Treinos</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($alunos) === 0): ?>
                    <tr>
                        <td colspan="5">Nenhum aluno encontrado.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($alunos as $aluno):
                        $numeroTreinos = (int) ($aluno['numero_treinos'] ?? 0);
                        $treinosNecessarios = (int) ($aluno['treinos'] ?? 0);
                        $resalvas = (int) ($aluno['resalvas'] ?? 0);
                        $treinosComResalvas = $treinosNecessarios + $resalvas * 10;
                        $treinosRestantes = max(0, $treinosComResalvas - $numeroTreinos);
                        $corFaixa = $aluno['faixa_atual'] ?? '#000';
                        ?>
                        <tr>
                            <td data-label="Nome"><?= htmlspecialchars($aluno['nome']) ?></td>
                            <td data-label="Faixa Atual" style="color: <?= htmlspecialchars($corFaixa) ?>;">
                                <?= htmlspecialchars($aluno['faixa_atual'] ?? 'Não definida') ?>
                            </td>
                            <td data-label="Último Exame">
                                <?= $aluno['data_ultimo_exame'] ? date('d/m/Y', strtotime($aluno['data_ultimo_exame'])) : 'Sem exame' ?>
                            </td>
                            <td data-label="Treinos">
                                <?= $numeroTreinos ?> / <?= $treinosComResalvas ?> (restam <?= $treinosRestantes ?>)
                            </td>
                            <td data-label="Status">
    <?php if ($aluno['ativo']): ?>
        <span style="color:green;">Ativo</span>
        <a href="aluno-toggle-status.php?id=<?= $aluno['usuario_id'] ?>&status=0"
           onclick="return confirm('Deseja desativar este aluno?')"
           style="margin-left:10px; color:red; text-decoration:none;">❌ Desativar</a>
    <?php else: ?>
        <span style="color:red;">Inativo</span>
        <a href="aluno-toggle-status.php?id=<?= $aluno['usuario_id'] ?>&status=1"
           onclick="return confirm('Deseja ativar este aluno?')"
           style="margin-left:10px; color:green; text-decoration:none;">✅ Ativar</a>
    <?php endif; ?>
</td>

                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
