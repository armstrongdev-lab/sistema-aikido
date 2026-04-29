<?php
include '../includes/header.php';
require_once '../includes/db.php';

$aluno_id = $_SESSION['usuario_id'];

// Busca o ID do aluno a partir do ID do usuário logado
$stmt = $pdo->prepare("SELECT id FROM alunos WHERE usuario_id = ?");
$stmt->execute([$aluno_id]);
$aluno = $stmt->fetch();

if (!$aluno) {
    echo "<p>Aluno não encontrado.</p>";
    include '../includes/footer.php';
    exit;
}

// Lista as mensalidades
$stmtMensalidades = $pdo->prepare("
    SELECT * FROM mensalidades
    WHERE aluno_id = ?
    ORDER BY mes_ano DESC
");
$stmtMensalidades->execute([$aluno['id']]);
$mensalidades = $stmtMensalidades->fetchAll();
?>

<style>
    .painel-aluno {
        background-color: #fff;
        border-radius: 12px;
        padding: 20px;
        margin-top: 20px;
        box-shadow: 0 0 8px rgba(0, 0, 0, 0.1);
    }

    .painel-aluno h2 {
        text-align: center;
        margin-bottom: 20px;
        color: #333;
    }

    .tabela {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }

    .tabela thead {
        background-color: #f4f4f4;
    }

    .tabela th,
    .tabela td {
        padding: 10px;
        border: 1px solid #ddd;
        text-align: center;
        font-size: 15px;
    }

    .tabela tr:nth-child(even) {
        background-color: #fafafa;
    }

    .tabela span {
        font-weight: bold;
    }

    .tabela span[style*="green"] {
        color: green;
    }

    .tabela span[style*="red"] {
        color: red;
    }
</style>

<div class="container painel-aluno">
    <h2>Minhas Mensalidades</h2>

    <?php if (empty($mensalidades)): ?>
        <p>Nenhuma mensalidade cadastrada ainda.</p>
    <?php else: ?>
        <table class="tabela">
            <thead>
                <tr>
                    <th>Mês</th>
                    <th>Valor</th>
                    <th>Status</th>
                    <th>Data de Pagamento</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($mensalidades as $m): ?>
                    <tr>
                        <td><?= htmlspecialchars($m['mes_ano']) ?></td>
                        <td>R$ <?= number_format($m['valor'], 2, ',', '.') ?></td>
                        <td>
                            <?php
                            if ($m['status'] === 'Paga') {
                                echo '<span style="color:green;">Paga</span>';
                            } elseif ($m['status'] === 'Ausente') {
                                echo '<span style="color:orange;">Ausente</span>';
                            } else {
                                echo '<span style="color:red;">Em Aberto</span>';
                            }
                            ?>
                        </td>
                        <td><?= $m['data_pagamento'] ? date('d/m/Y', strtotime($m['data_pagamento'])) : '-' ?></td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    <?php endif ?>
</div>

<?php include '../includes/footer.php'; ?>