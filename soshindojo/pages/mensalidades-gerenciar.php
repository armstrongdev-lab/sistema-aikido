<?php
include '../includes/header.php';
require_once '../includes/db.php';

if ($_SESSION['tipo_usuario'] !== 'sensei' && $_SESSION['tipo_usuario'] !== 'admin') {
    echo "<p>Acesso negado.</p>";
    include '../includes/footer.php';
    exit;
}

// Marcar como paga
if (isset($_GET['baixar'])) {
    $id = $_GET['baixar'];

    // Primeiro: pegar status atual da mensalidade ANTES de atualizar
    $stmt = $pdo->prepare("SELECT status, aluno_id FROM mensalidades WHERE id = ?");
    $stmt->execute([$id]);
    $mensalidade = $stmt->fetch(PDO::FETCH_ASSOC);

    // Atualiza mensalidade para Paga
    $stmt = $pdo->prepare("UPDATE mensalidades SET status = 'Paga', data_pagamento = CURDATE() WHERE id = ?");
    $stmt->execute([$id]);

    // Se a mensalidade estava Ausente, reativa o usuário
    if ($mensalidade && $mensalidade['status'] === 'Ausente') {
        // Pega usuario_id via aluno_id
        $stmt = $pdo->prepare("
            SELECT u.id AS usuario_id
            FROM alunos a
            JOIN usuarios u ON a.usuario_id = u.id
            WHERE a.id = ?
        ");
        $stmt->execute([$mensalidade['aluno_id']]);
        $usuario = $stmt->fetch();

        if ($usuario) {
            $stmt = $pdo->prepare("UPDATE usuarios SET ativo = 1 WHERE id = ?");
            $stmt->execute([$usuario['usuario_id']]);
        }
    }
}


// Marcar como ausente
if (isset($_GET['ausente'])) {
    $id = $_GET['ausente'];

    // Atualiza mensalidade para Ausente com data_pagamento NULL
    $stmt = $pdo->prepare("UPDATE mensalidades SET status = 'Ausente', data_pagamento = NULL WHERE id = ?");
    $stmt->execute([$id]);

    // Pega o usuario_id a partir da mensalidade
    $stmt = $pdo->prepare("
        SELECT u.id AS usuario_id
        FROM mensalidades m
        JOIN alunos a ON m.aluno_id = a.id
        JOIN usuarios u ON a.usuario_id = u.id
        WHERE m.id = ?
    ");
    $stmt->execute([$id]);
    $usuario = $stmt->fetch();

    // Se encontrou o usuário, desativa
    if ($usuario) {
        $stmt = $pdo->prepare("UPDATE usuarios SET ativo = 0 WHERE id = ?");
        $stmt->execute([$usuario['usuario_id']]);
    }
}

// Filtros
$statusFiltro = $_GET['status'] ?? '';
$mesSelecionado = $_GET['mes'] ?? '';
if (!isset($_GET['mes']) || empty($_GET['mes'])) {
    $mesSelecionado = date('Y-m'); // formato "YYYY-MM", para o input type="month"
} else {
    $mesSelecionado = $_GET['mes'];
}


// Monta a query com filtros
$sql = "
    SELECT m.*, u.nome
    FROM mensalidades m
    JOIN alunos a ON m.aluno_id = a.id
    JOIN usuarios u ON a.usuario_id = u.id
    WHERE 1
";
$params = [];

if (in_array($statusFiltro, ['Paga', 'Em Aberto', 'Ausente'])) {
    $sql .= " AND m.status = ?";
    $params[] = $statusFiltro;
}

if (!empty($mesSelecionado)) {
    $mesAnoFormatado = $mesSelecionado;
    $sql .= " AND m.mes_ano = ?";
    $params[] = $mesAnoFormatado;
}

$sql .= " ORDER BY m.mes_ano DESC, u.nome ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$mensalidades = $stmt->fetchAll();
?>


<style>
    form {
        display: flex;
        flex-direction: row;
        gap: 1rem;
        max-width: 700px;
        margin: auto;
        text-align: left;
    }

    .container h2 {
        font-size: 26px;
        margin-bottom: 20px;
        text-align: center;
        color: #333;
    }

    .filtros {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 25px;
        justify-content: center;
    }

    .filtros label {
        font-weight: bold;
        margin-right: 5px;
    }

    .filtros select,
    .filtros input,
    .filtros button {
        padding: 8px 12px;
        border-radius: 6px;
        border: 1px solid #ccc;
        font-size: 14px;
    }

    .filtros select {
        min-width: 120px;
    }

    .filtros input[type="month"] {
        min-width: 150px;
    }

    .filtros button {
        background-color: #6c757d;
        color: white;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
        border: none;
        transition: background-color 0.2s;
    }

    .filtros button:hover {
        background-color: #5a6268;
    }

    .tabela {
        width: 100%;
        border-collapse: collapse;
        background: #fff;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    }

    .tabela thead {
        background-color: #007bff;
        color: white;
    }

    .tabela th,
    .tabela td {
        padding: 12px 15px;
        text-align: center;
        border-bottom: 1px solid #ddd;
    }

    .tabela tr:hover {
        background-color: #f9f9f9;
    }

    .tabela td .btn-baixar {
        background-color: #28a745;
        color: white;
        padding: 6px 10px;
        border-radius: 5px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 14px;
        text-decoration: none;
        transition: background-color 0.2s;
    }

    .tabela td .btn-baixar:hover {
        background-color: #218838;
    }

    .status-pago {
        color: #28a745;
        font-weight: bold;
    }

    .status-aberto {
        color: #dc3545;
        font-weight: bold;
    }

    .status-ausente {
        color: #ff6600;
        /* Laranja para diferenciar */
        font-weight: bold;
    }


    .tabela td .btn-ausente {
        background-color: #dc3545;
        color: white;
        padding: 6px 10px;
        border-radius: 5px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 14px;
        text-decoration: none;
        transition: background-color 0.2s;
    }

    .tabela td .btn-ausente:hover {
        background-color: #c82333;
    }


    @media (max-width: 768px) {
        .tabela thead {
            display: none;
        }

        .tabela tr {
            display: block;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 10px;
        }

        .tabela td {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            border: none;
        }

        .tabela td::before {
            content: attr(data-label);
            font-weight: bold;
            color: #333;
        }
    }
</style>
<div class="container">
    <h2>Gerenciar Mensalidades</h2>

    <form method="GET" class="filtros">
        <div>
            <label for="status"><i class="fas fa-filter"></i>Status:</label>
            <select name="status" id="status" onchange="this.form.submit()">
                <option value="">Todos</option>
                <option value="Paga" <?= $statusFiltro === 'Paga' ? 'selected' : '' ?>>✅ Pagas</option>
                <option value="Em Aberto" <?= $statusFiltro === 'Em Aberto' ? 'selected' : '' ?>>❌ Pendentes</option>
                <option value="Ausente" <?= $statusFiltro === 'Ausente' ? 'selected' : '' ?>>⚠️ Ausentes</option>
            </select>
        </div>

        <div>
            <label for="mes"><i class="far fa-calendar-alt"></i>Mês:</label>
            <input type="month" name="mes" id="mes" value="<?= htmlspecialchars($mesSelecionado) ?>"
                onchange="this.form.submit()">
        </div>

        <div>
            <button type="button" onclick="window.location.href='<?= basename($_SERVER['PHP_SELF']) ?>'">
                <i class="fas fa-undo"></i> Limpar Filtros
            </button>
        </div>
    </form>

    <table class="tabela">
        <thead>
            <tr>
                <th>Aluno</th>
                <th>Mês</th>
                <th>Valor</th>
                <th>Status</th>
                <th>Data Pagamento</th>
                <th>Ação</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($mensalidades as $m): ?>
                <tr>
                    <td data-label="Aluno"><?= htmlspecialchars($m['nome']) ?></td>
                    <td data-label="Mês"><?= htmlspecialchars($m['mes_ano']) ?></td>
                    <td data-label="Valor">R$ <?= number_format($m['valor'], 2, ',', '.') ?></td>
                    <td data-label="Status">
                        <?php if ($m['status'] === 'Paga'): ?>
                            <span class="status-pago"><i class="fas fa-check-circle"></i> Paga</span>
                        <?php elseif ($m['status'] === 'Ausente'): ?>
                            <span class="status-ausente"><i class="fas fa-user-times"></i> Ausente</span>
                        <?php else: ?>
                            <span class="status-aberto"><i class="fas fa-times-circle"></i> Em Aberto</span>
                        <?php endif; ?>
                    </td>
                    <td data-label="Data Pagamento">
                        <?= $m['data_pagamento'] ? date('d/m/Y', strtotime($m['data_pagamento'])) : '-' ?>
                    </td>
                    <td data-label="Ação">
                        <?php if ($m['status'] === 'Em Aberto' || $m['status'] === 'Ausente'): ?>
                            <a href="?baixar=<?= $m['id'] ?>" class="btn-baixar"
                                onclick="return confirm('Confirmar baixa desta mensalidade?')">
                                <i class="fas fa-cash-register"></i> Baixar
                            </a>

                            <?php if ($m['status'] === 'Em Aberto'): ?>
                                <a href="?ausente=<?= $m['id'] ?>" class="btn-ausente"
                                    onclick="return confirm('Marcar este aluno como ausente?')">
                                    <i class="fas fa-user-times"></i> Ausente
                                </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <i class="fas fa-lock text-muted" title="Ação indisponível"></i>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
</div>

<?php include '../includes/footer.php'; ?>