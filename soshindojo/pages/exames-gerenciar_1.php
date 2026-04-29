<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!in_array($_SESSION['tipo_usuario'], ['sensei', 'admin'])) {
    header('Location: ../index.php');
    exit;
}

$isAdmin = $_SESSION['tipo_usuario'] === 'admin';
$isSensei = $_SESSION['tipo_usuario'] === 'sensei';

require_once '../includes/db.php';
include '../includes/header.php';

// Obter dojo_id do sensei logado (para filtro)
$senseiDojoId = null;
if ($isSensei) {
    $sensei_id = $_SESSION['usuario_id'];
    $stmtDojo = $pdo->prepare("SELECT dojo_id FROM usuarios WHERE id = :id");
    
    $stmtDojo->bindParam(':id', $sensei_id, PDO::PARAM_INT);
    $stmtDojo->execute();
    $senseiDojoId = $stmtDojo->fetchColumn();
}


// Buscar lista de dojos só para admin (para filtro no select)
$dojos = [];
if ($isAdmin) {
    $stmtDojoList = $pdo->query("SELECT id, nome FROM dojos ORDER BY nome");
    $dojos = $stmtDojoList->fetchAll(PDO::FETCH_ASSOC);
}

// Construir query base e parâmetros para filtro
$filtros = [];
$parametros = [];

// Filtro nome
if (!empty($_GET['nome'])) {
    $filtros[] = "u.nome LIKE :nome";
    $parametros[':nome'] = "%" . $_GET['nome'] . "%";
}

// Só admin pode filtrar por dojo via GET
if ($isAdmin && !empty($_GET['dojo'])) {
    $dojo_id = intval($_GET['dojo']);
    if ($dojo_id > 0) {
        $filtros[] = "a.dojo_id = :dojo_id";
        $parametros[':dojo_id'] = $dojo_id;
    }
} elseif ($isSensei) {
    // Sensei só vê alunos do seu dojo (dojo_id obtido do login)
    if (!empty($senseiDojoId)) {
        $filtros[] = "a.dojo_id = :dojo_id";
        $parametros[':dojo_id'] = $senseiDojoId;
    } else {
        // Se sensei não tem dojo, melhor não mostrar alunos (evita erro)
        $filtros[] = "1=0";
    }
}


// Montar consulta SQL completa
$sql = "
SELECT 
    a.id AS aluno_id,
    u.nome,
    a.graduacao,
    a.data_inicio,
    d.nome AS nome_dojo,  -- <-- Adicionado aqui
    ft.numero_treinos,
    f.faixa_cor,
    f.tempo_minimo_meses,
    f.treinos AS treinos_necessarios,
    (
        SELECT COUNT(*) 
        FROM exames e 
        WHERE e.aluno_id = a.id
    ) AS exames_realizados,
    (
        SELECT e.resalvas 
        FROM exames e 
        WHERE e.aluno_id = a.id
        ORDER BY e.data_exame DESC LIMIT 1
    ) AS ultima_resalva,
    (
        SELECT e.situacao 
        FROM exames e 
        WHERE e.aluno_id = a.id
        ORDER BY e.data_exame DESC LIMIT 1
    ) AS situacao_exame
FROM alunos a
JOIN usuarios u ON a.usuario_id = u.id
JOIN dojos d ON a.dojo_id = d.id  -- <-- JOIN com a tabela de dojos
JOIN faixas f ON a.graduacao = f.id
LEFT JOIN (
    SELECT ft1.aluno_id, ft1.id_faixa, ft1.numero_treinos
    FROM faixas_treinos ft1
    INNER JOIN (
        SELECT aluno_id, MAX(data_inicio) AS max_data
        FROM faixas_treinos
        GROUP BY aluno_id
    ) ft2 ON ft1.aluno_id = ft2.aluno_id AND ft1.data_inicio = ft2.max_data
) ft ON a.id = ft.aluno_id
";

// Adicionar cláusula WHERE se houver filtros
if (count($filtros) > 0) {
    $sql .= " WHERE " . implode(" AND ", $filtros);
}

$sql .= " ORDER BY u.nome";

$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);
$alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Aplicar filtro 'apto' via PHP (já que depende de lógica customizada)
$filtro_apto = $_GET['apto'] ?? '';

if ($filtro_apto !== '') {
    $filtro_nome = $_GET['nome'] ?? '';

    $alunos = array_filter($alunos, function ($aluno) use ($filtro_nome, $filtro_apto) {
        $dataInicio = new DateTime($aluno['data_inicio']);
        $hoje = new DateTime();
        $intervalo = $dataInicio->diff($hoje);
        $mesesDeTreino = $intervalo->y * 12 + $intervalo->m;

        $numeroTreinos = (int) $aluno['numero_treinos'];
        $tempoMinimoMeses = (int) $aluno['tempo_minimo_meses'];
        $resalvas = isset($aluno['ultima_resalva']) ? (int) $aluno['ultima_resalva'] : 0;
        $treinosNecessariosAjustados = $aluno['treinos_necessarios'] + ($resalvas * 10);
        $apto = ($numeroTreinos >= $treinosNecessariosAjustados) && ($mesesDeTreino >= $tempoMinimoMeses);

        // Filtro por nome
        $nomeCond = true;
        if (!empty($filtro_nome)) {
            $nomeCond = stripos($aluno['nome'], $filtro_nome) !== false;
        }

        // Filtro por apto/não apto
        $aptoCond = true;
        if ($filtro_apto === '1') {
            $aptoCond = $apto;
        } elseif ($filtro_apto === '0') {
            $aptoCond = !$apto;
        }

        return $nomeCond && $aptoCond;
    });
}
?>
<style>
    .modal-aprovar {
        display: none;
        position: fixed;
        top: 30%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: #fff;
        border: 1px solid #ccc;
        padding: 20px;
        z-index: 1000;
        box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.2);
        border-radius: 10px;
    }

    .modal-aprovar form {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 999;
    }

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

    .filtros .btn-limpar {
        padding: 8px 16px;
        background-color: #000;
        color: #fff;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        transition: background-color 0.3s;
        text-decoration: none;
        display: inline-block;
        text-align: center;
    }

    .filtros .btn-limpar:hover {
        background-color: #c0c0c0;
        color: #000;
    }

    @media (max-width: 600px) {
        .filtros form {
            flex-direction: column;
            align-items: stretch;
        }

        .filtros input[type="text"],
        .filtros select,
        .filtros button,
        .filtros .btn-limpar {
            width: 100%;
            text-align: center;
        }

        .filtros .btn-limpar {
            display: block;
            margin: 0 auto;
        }
    }

    .mensagem-exame {
        margin: 15px 0;
        padding: 15px 20px;
        border-radius: 8px;
        font-weight: bold;
        font-size: 16px;
        color: #fff;
        background-color: #28a745;
        /* Verde para sucesso */

        /* Sombra leve para destacar */
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);

        /* Animação de fade in */
        animation: fadeInMsg 0.5s ease-in;
    }

    /* Caso queira também mensagens de erro, pode criar outra classe */
    /* Exemplo: .mensagem-erro para mensagens vermelhas */

    .mensagem-erro {
        background-color: #dc3545;
        /* vermelho para erro */
        color: #fff;
        margin: 15px 0;
        padding: 15px 20px;
        border-radius: 8px;
        font-weight: bold;
        font-size: 16px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        animation: fadeInMsg 0.5s ease-in;
    }

    /* Animação simples fade in */
    @keyframes fadeInMsg {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }
    
</style>

<div class="exames-gerenciar">
    <div class="container">
        <h1>Gerenciar Exames</h1>
        <?php if (isset($_SESSION['mensagem'])): ?>
            <div class="mensagem-exame">
                <?= htmlspecialchars($_SESSION['mensagem']) ?>
                <?php unset($_SESSION['mensagem']); ?>
            </div>
        <?php endif; ?>

        <div class="filtros">
            <form method="GET" action="">
                <input type="text" name="nome" placeholder="Buscar por nome"
                    value="<?= htmlspecialchars($_GET['nome'] ?? '') ?>">

                <select name="apto">
                    <option value="">Todos</option>
                    <option value="1" <?= (isset($_GET['apto']) && $_GET['apto'] === '1') ? 'selected' : '' ?>>Aptos
                    </option>
                    <option value="0" <?= (isset($_GET['apto']) && $_GET['apto'] === '0') ? 'selected' : '' ?>>Não Aptos
                    </option>
                </select>

                <?php if ($_SESSION['tipo_usuario'] === 'admin'): ?>
    <select name="dojo">
        <option value="">Todos os Dojos</option>
        <?php foreach ($dojos as $dojo): ?>
            <option value="<?= htmlspecialchars($dojo['id']) ?>" <?= (isset($_GET['dojo']) && $_GET['dojo'] == $dojo['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($dojo['nome']) ?>
            </option>
        <?php endforeach; ?>
    </select>
<?php endif; ?>


                <button type="submit">🔍 Filtrar</button>
                <a href="exames-gerenciar.php" class="btn-limpar">🧹 Limpar Filtros</a>
            </form>

        </div>
        <div class="tabela-responsiva">
            <table class="tabela-exames">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Faixa Atual</th>
                        <th class="col-inicio">Ultimo Exame</th>
                        <th class="col-meses">Meses</th>
                        <th>Treinos Feitos</th>
                        <th class="col-treinos">Treinos Necessários</th>
                        <th class="col-tempo">Tempo Mínimo</th>
                        <th>Status</th>
                        <th>Exames</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>

                    <?php
                    if ($_SESSION['tipo_usuario'] === 'admin') {
                        $stmt = $pdo->query("SELECT DISTINCT dojo_id FROM alunos ORDER BY dojo_id");
                        $dojos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    }

                    foreach ($alunos as $aluno):
                        $dataInicio = new DateTime($aluno['data_inicio']);
                        $hoje = new DateTime();
                        $intervalo = $dataInicio->diff($hoje);
                        $mesesDeTreino = $intervalo->y * 12 + $intervalo->m;

                        $numeroTreinos = (int) $aluno['numero_treinos'];
                        $tempoMinimoMeses = (int) $aluno['tempo_minimo_meses'];
                        $resalvas = isset($aluno['ultima_resalva']) ? (int) $aluno['ultima_resalva'] : 0;

                        $treinosNecessariosAjustados = $aluno['treinos_necessarios'] + ($resalvas * 10);
                        $apto = ($numeroTreinos >= $treinosNecessariosAjustados) &&
                            ($mesesDeTreino >= $tempoMinimoMeses);
                        $jaFezExame = $aluno['exames_realizados'] > 0;
                        ?>
                        <tr class="<?= $apto ? 'linha-apto' : '' ?>">
                            <td><?= htmlspecialchars($aluno['nome']) ?></td>
                            <td><?= htmlspecialchars($aluno['faixa_cor']) ?></td>
                            <td class="col-inicio"><?= date('d/m/Y', strtotime($aluno['data_inicio'])) ?></td>
                            <td class="col-meses"><?= $mesesDeTreino ?></td>
                            <td><?= $numeroTreinos ?></td>
                            <td class="col-treinos"><?= $treinosNecessariosAjustados ?></td>
                            <td class="col-tempo"><?= $tempoMinimoMeses ?></td>

                            <td>
                                <?php if ($apto): ?>
                                    <span style="color: green;">Apto</span>
                                <?php else: ?>
                                    <span style="color: red;">Não Apto</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($jaFezExame):
                                    $situacao = $aluno['situacao_exame'];
                                    $resalvas = (int) $aluno['ultima_resalva'];
                                    if ($situacao === 'aprovado' && $resalvas > 0) {
                                        $situacao = 'Aprovado c/';
                                    } else {
                                        $situacao = ucfirst($situacao);
                                    }
                                    ?>
                                    <?= $situacao ?>         <?= $resalvas > 0 ? " (Resalvas: $resalvas)" : "" ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($apto): ?>
                                    <button onclick="abrirModal(<?= $aluno['aluno_id'] ?>, '<?= $aluno['faixa_cor'] ?>')">Aprovar</button>
                                <?php else: ?>
                                    <button disabled>Aprovar</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal-overlay" id="overlay"></div>
<div class="modal-aprovar" id="modal-aprovar">
    <form action="exames-aprovar.php" method="POST">
        <input type="hidden" name="aluno_id" id="modal-aluno-id">
        <input type="hidden" name="situacao" id="modal-situacao" value="aprovado">

        <label>Resalvas:
            <input type="number" name="resalvas" min="0" max="5" value="0" style="width: 60px;">
        </label>

        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
    <button type="submit" onclick="document.getElementById('modal-situacao').value='aprovado'">Aprovar</button>

    <button type="submit" onclick="document.getElementById('modal-situacao').value='reprovado'"
        style="background-color: red; color: white;">Reprovado</button>

    <button type="submit" id="btn-graduar" onclick="document.getElementById('modal-situacao').value='graduar_amarela'"
    style="display: none; background-color: goldenrod; color: white;">Graduar p/ Faixa Amarela</button>

    <button type="button" onclick="fecharModal()">Cancelar</button>
</div>

    </form>
</div>

<script>
    function abrirModal(alunoId, faixaCor) {
        document.getElementById('modal-aluno-id').value = alunoId;

        // Exibe ou esconde o botão "Graduar"
        const btnGraduar = document.getElementById('btn-graduar');
        if (faixaCor.trim().toLowerCase() === 'branca') {
            btnGraduar.style.display = 'inline-block';
        } else {
            btnGraduar.style.display = 'none';
        }

        document.getElementById('modal-aprovar').style.display = 'block';
        document.getElementById('overlay').style.display = 'block';
    }

    function fecharModal() {
        document.getElementById('modal-aprovar').style.display = 'none';
        document.getElementById('overlay').style.display = 'none';
    }
</script>


<?php include '../includes/footer.php'; ?>