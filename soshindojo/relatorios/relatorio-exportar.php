<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$dojo_id = $_SESSION['dojo_id'] ?? null;
if (!$dojo_id) {
    echo "Dojo não identificado.";
    exit;
}

// Filtros
$nomeFiltro = $_GET['nome'] ?? '';
$anoFiltro = $_GET['ano'] ?? date('Y');

// Lista de anos distintos dos exames para filtro
$stmtAnos = $pdo->prepare("SELECT DISTINCT YEAR(data_exame) AS ano FROM exames ORDER BY ano DESC");
$stmtAnos->execute();
$anosDisponiveis = $stmtAnos->fetchAll(PDO::FETCH_COLUMN);

// Consulta alunos do dojo
$stmtAlunos = $pdo->prepare("SELECT a.id, u.nome FROM alunos a INNER JOIN usuarios u ON a.usuario_id = u.id WHERE a.dojo_id = ? AND u.nome LIKE ? ORDER BY u.nome");
$stmtAlunos->execute([$dojo_id, "%$nomeFiltro%"]);
$alunos = $stmtAlunos->fetchAll(PDO::FETCH_ASSOC);

// Mapeamento das faixas para valores numéricos
$mapaFaixas = [
    'Branca' => 1,
    'Laranja' => 2,
    'Amarela' => 3,
    'Roxa' => 4,
    'Verde' => 5,
    'Azul' => 6,
    'Marrom' => 7,
    'Preta' => 8
];
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Relatório de Alunos</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Estilo geral da tabela */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }

        /* Container do relatório do aluno */
        .relatorio-aluno {
            margin-bottom: 40px;
        }

        /* Estilo para cards de exames (mobile) */
        .exame-card {
            display: none;
            border: 1px solid #ccc;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 12px;
            background-color: #f9f9f9;
        }

        .exame-card p {
            margin: 6px 0;
            font-size: 0.9rem;
        }

        /* Filtros */
        .filtros {
            margin-bottom: 20px;
        }

        /* Responsividade */
        @media (max-width: 768px) {
            /* Esconder tabela no mobile */
            table {
                display: none;
            }

            /* Mostrar cards no mobile */
            .exame-card {
                display: block;
            }
        }

        /* Gráfico */
        .grafico {
            max-width: 700px;
            margin: 30px auto;
        }
    </style>
</head>

<body>
    <?php include '../includes/header.php'; ?>
    <div class="container">
        <h2>Relatório de Alunos</h2>

        <form method="GET" class="filtros">
            <input type="text" name="nome" placeholder="Buscar por nome" value="<?= htmlspecialchars($nomeFiltro) ?>">
            <select name="ano">
                <option value="">Todos os anos</option>
                <?php foreach ($anosDisponiveis as $ano): ?>
                    <option value="<?= $ano ?>" <?= ($ano == $anoFiltro) ? 'selected' : '' ?>><?= $ano ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Filtrar</button>
            <a href="relatorio_excel.php?nome=<?= urlencode($nomeFiltro) ?>&ano=<?= urlencode($anoFiltro) ?>" class="botao">Exportar Excel</a>
        </form>

        <?php foreach ($alunos as $aluno): ?>
            <div class="relatorio-aluno">
                <h3><?= htmlspecialchars($aluno['nome']) ?></h3>

                <?php
                $dataFim = date('Y-m-d');
                $dataInicio = date('Y-m-d', strtotime('-12 months'));

                // Presenças nos últimos 12 meses
                $stmtFreq = $pdo->prepare("SELECT COUNT(*) FROM presencas WHERE aluno_id = ? AND data_presenca BETWEEN ? AND ?");
                $stmtFreq->execute([$aluno['id'], $dataInicio, $dataFim]);
                $presencas = (int) $stmtFreq->fetchColumn();
                $presencaIdeal = 12 * 8; // 8 treinos/mês
                $percentual = $presencaIdeal > 0 ? round(($presencas / $presencaIdeal) * 100, 1) : 0;
                ?>

                <strong>Frequência nos últimos 12 meses:</strong>
                <ul>
                    <li>Presenças: <?= $presencas ?> / <?= $presencaIdeal ?> (<?= $percentual ?>%)</li>
                </ul>

                <strong>Histórico de Exames<?= $anoFiltro ? " ($anoFiltro)" : '' ?>:</strong>

                <?php
                $queryExames = "SELECT e.data_exame, f.faixa_cor AS faixa, e.resalvas FROM exames e 
                                INNER JOIN faixas f ON e.id_faixa = f.id 
                                WHERE e.aluno_id = ? ";
                if ($anoFiltro) {
                    $queryExames .= " AND YEAR(e.data_exame) = ? ";
                }
                $queryExames .= " ORDER BY e.data_exame ASC";
                $stmtExames = $pdo->prepare($queryExames);
                $params = [$aluno['id']];
                if ($anoFiltro)
                    $params[] = $anoFiltro;
                $stmtExames->execute($params);
                $exames = $stmtExames->fetchAll(PDO::FETCH_ASSOC);
                ?>

                <?php if ($exames): ?>
                    <!-- Tabela para desktop -->
                    <table>
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Faixa</th>
                                <th>Ressalva</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($exames as $exame): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($exame['data_exame'])) ?></td>
                                    <td><?= htmlspecialchars($exame['faixa']) ?></td>
                                    <td><?= $exame['resalvas'] ? 'Sim' : 'Não' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Cards para mobile -->
                    <?php foreach ($exames as $exame): ?>
                        <div class="exame-card">
                            <p><strong>Data:</strong> <?= date('d/m/Y', strtotime($exame['data_exame'])) ?></p>
                            <p><strong>Faixa:</strong> <?= htmlspecialchars($exame['faixa']) ?></p>
                            <p><strong>Ressalva:</strong> <?= $exame['resalvas'] ? 'Sim' : 'Não' ?></p>
                        </div>
                    <?php endforeach; ?>

                    <?php
                    // Dados para gráfico
                    $labelsDatas = array_map(fn($e) => date('d/m/Y', strtotime($e['data_exame'])), $exames);
                    $dadosFaixa = array_map(fn($e) => $mapaFaixas[$e['faixa']] ?? 0, $exames);

                    $dadosPresencas = [];
                    $stmtPresencaExame = $pdo->prepare("SELECT COUNT(*) FROM presencas WHERE aluno_id = ? AND data_presenca <= ?");
                    foreach ($exames as $exame) {
                        $stmtPresencaExame->execute([$aluno['id'], $exame['data_exame']]);
                        $dadosPresencas[] = (int) $stmtPresencaExame->fetchColumn();
                    }
                    ?>
                    <div class="grafico">
                        <canvas id="grafico-<?= $aluno['id'] ?>"></canvas>
                    </div>
                    <script>
                        const ctx<?= $aluno['id'] ?> = document.getElementById('grafico-<?= $aluno['id'] ?>').getContext('2d');
                        new Chart(ctx<?= $aluno['id'] ?>, {
                            type: 'line',
                            data: {
                                labels: <?= json_encode($labelsDatas) ?>,
                                datasets: [
                                    {
                                        label: 'Evolução da Faixa',
                                        data: <?= json_encode($dadosFaixa) ?>,
                                        borderColor: 'rgba(75, 192, 192, 1)',
                                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                                        yAxisID: 'y-faixa',
                                        tension: 0.3,
                                        fill: true,
                                        pointRadius: 5,
                                        pointHoverRadius: 7
                                    },
                                    {
                                        label: 'Presenças acumuladas',
                                        data: <?= json_encode($dadosPresencas) ?>,
                                        borderColor: 'rgba(255, 159, 64, 1)',
                                        backgroundColor: 'rgba(255, 159, 64, 0.2)',
                                        yAxisID: 'y-presencas',
                                        tension: 0.3,
                                        fill: true,
                                        pointRadius: 5,
                                        pointHoverRadius: 7
                                    }
                                ]
                            },
                            options: {
                                scales: {
                                    'y-faixa': {
                                        type: 'linear',
                                        position: 'left',
                                        title: { display: true, text: 'Nível da Faixa' },
                                        min: 0,
                                        max: 8,
                                        ticks: {
                                            stepSize: 1,
                                            callback: function(value) {
                                                const faixas = ['-', 'Branca', 'Laranja', 'Amarela', 'Roxa', 'Verde', 'Azul', 'Marrom', 'Preta'];
                                                return faixas[value] || value;
                                            }
                                        }
                                    },
                                    'y-presencas': {
                                        type: 'linear',
                                        position: 'right',
                                        title: { display: true, text: 'Presenças' },
                                        grid: { drawOnChartArea: false },
                                    }
                                },
                                interaction: {
                                    mode: 'index',
                                    intersect: false,
                                },
                                plugins: {
                                    legend: {
                                        position: 'top',
                                    },
                                    tooltip: {
                                        enabled: true,
                                    }
                                }
                            }
                        });
                    </script>
                <?php else: ?>
                    <p>Nenhum exame encontrado.</p>
                <?php endif; ?>

            </div>
        <?php endforeach; ?>

    </div>
</body>

</html>
