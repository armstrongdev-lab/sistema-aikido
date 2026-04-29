<?php
// Configura sessão para durar 1 hora
ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params(3600);

session_start();

// Verifica se usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}

// Guarda aviso de mensalidade, se existir
$mensagem_mensalidade = false;
if (!empty($_SESSION['mensalidade_em_aberto'])) {
    $mensagem_mensalidade = true;
    unset($_SESSION['mensalidade_em_aberto']);
}

// Impede cache
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once '../includes/db.php';

$usuario_id = $_SESSION['usuario_id'];

// Consulta os dados do aluno e usuário
$stmtAluno = $pdo->prepare("
    SELECT u.id AS usuario_id, u.nome, u.email, a.id AS aluno_id, a.telefone, a.endereco, 
       a.data_nascimento, a.data_inicio, a.graduacao, d.nome AS dojo_nome, d.dojoLatitude, d.dojoLongitude, d.distanciaPermitida
    FROM usuarios u
    INNER JOIN alunos a ON u.id = a.usuario_id
    INNER JOIN dojos d ON a.dojo_id = d.id
    WHERE u.id = ?
");
$stmtAluno->execute([$usuario_id]);
$aluno = $stmtAluno->fetch();

if (!$aluno) {
    echo "Aluno não encontrado.";
    exit;
}

// Buscar faixa atual e número de treinos na tabela faixas_treinos para este aluno
$stmtTreinos = $pdo->prepare("
    SELECT f.id AS id_faixa, f.faixa_cor AS faixa, ft.data_inicio
    FROM faixas_treinos ft
    INNER JOIN faixas f ON ft.id_faixa = f.id
    WHERE ft.aluno_id = ?
    ORDER BY ft.data_inicio DESC
    LIMIT 1
");
$stmtTreinos->execute([$aluno['aluno_id']]);
$faixaDados = $stmtTreinos->fetch();

$faixaAtual = $faixaDados ? $faixaDados['faixa'] : 'Não definida';
$idFaixaAtual = $faixaDados ? $faixaDados['id_faixa'] : null;
$dataInicioFaixa = $faixaDados ? $faixaDados['data_inicio'] : null;

$numeroTreinos = 0;
$treinosNecessarios = 0;
$tempoMinimoMeses = 0;
$proximaFaixa = '---';
$mesesDecorridos = 0;
$resalvas = 0;

// Trata caso graduação seja 0 (sensei)
if ($aluno['graduacao'] === 0) {
    $faixaAtual = 'Sensei';
    $proximaFaixa = 'Máxima';
    $percentual = 100;
} else if ($dataInicioFaixa && $idFaixaAtual) {
    // Número de treinos desde início da faixa
    $stmtPresencas = $pdo->prepare("
        SELECT COUNT(*) FROM presencas
        WHERE aluno_id = ? AND data_presenca >= ?
    ");
    $stmtPresencas->execute([$aluno['aluno_id'], $dataInicioFaixa]);
    $numeroTreinos = $stmtPresencas->fetchColumn();

    // Requisitos da faixa atual
    $stmtFaixaAtual = $pdo->prepare("
        SELECT treinos, tempo_minimo_meses
        FROM faixas
        WHERE id = ?
        LIMIT 1
    ");
    $stmtFaixaAtual->execute([$idFaixaAtual]);
    $requisitos = $stmtFaixaAtual->fetch();

    if ($requisitos) {
        $treinosNecessarios = $requisitos['treinos'];
        $tempoMinimoMeses = $requisitos['tempo_minimo_meses'];

        // Resalvas no último exame da faixa anterior
        $stmtResalva = $pdo->prepare("
            SELECT resalvas
            FROM exames
            WHERE aluno_id = ? AND id_faixa = (
                SELECT MAX(id_faixa)
                FROM exames
                WHERE aluno_id = ? AND id_faixa < ?
            )
            LIMIT 1
        ");
        $stmtResalva->execute([$aluno['aluno_id'], $aluno['aluno_id'], $idFaixaAtual]);
        $resalvas = (int) $stmtResalva->fetchColumn();

        // Acrescenta 10 treinos por resalva
        $treinosNecessarios += ($resalvas * 10);
    }

    // Calcula meses decorridos
    $inicio = new DateTime($dataInicioFaixa);
    $hoje = new DateTime();
    $intervalo = $inicio->diff($hoje);
    $mesesDecorridos = $intervalo->y * 12 + $intervalo->m;

    // Próxima faixa
    $stmtProxima = $pdo->prepare("
        SELECT faixa_cor
        FROM faixas
        WHERE id = ?
    ");
    $stmtProxima->execute([$aluno['graduacao'] + 1]);
    $proxima = $stmtProxima->fetch();
    $proximaFaixa = $proxima ? $proxima['faixa_cor'] : 'Máxima';

    // Percentual
    $percentual = ($treinosNecessarios > 0) ? min(100, round(($numeroTreinos / $treinosNecessarios) * 100)) : 0;
} else {
    $percentual = 0;
}
?>

<?php include '../includes/header.php'; ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<div class="container painel-aluno">
    <h2>Bem-vindo(a), <?= htmlspecialchars($aluno['nome']) ?>!</h2>

    <?php if ($mensagem_mensalidade): ?>
        <div class="mensagem-aviso" style="background-color: #fff3cd; padding: 15px; border-radius: 5px; margin-bottom: 20px; color: #856404;">
            ⚠ Sua mensalidade deste mês está em aberto.
        </div>
    <?php endif; ?>

    <a href="#" id="btn-marcar-presenca" class="btn-edit">Marcar Presença</a>
    <p id="status-presenca"></p>

    <div class="progresso-faixa">
        <p><strong>Progresso de Treinos:</strong> <?= $percentual ?>%</p>
        <div class="barra-externa">
            <div class="barra-interna" style="width: <?= $percentual ?>%;"></div>
        </div>
    </div>

    <h3>Seus Dados</h3>
    <ul class="lista-dados-aluno">
        <li><i class="fas fa-house-chimney"></i> <strong>Dojo:</strong> <?= htmlspecialchars($aluno['dojo_nome']) ?></li>
        <li><i class="fas fa-calendar-check"></i> <strong>Último exame:</strong> <?= htmlspecialchars($aluno['data_inicio']) ?></li>
        <li><i class="fas fa-times-circle"></i> <strong>Resalvas no último exame:</strong> <?= $resalvas ?></li>
        <li><i class="fas fa-signal"></i> <strong>Faixa Atual:</strong> <?= htmlspecialchars($faixaAtual) ?></li>
        <li><i class="fas fa-arrow-up"></i> <strong>Próxima Faixa:</strong> <?= htmlspecialchars($proximaFaixa) ?></li>
        <li><i class="fas fa-dumbbell"></i> <strong>Treinos na Faixa Atual:</strong> <?= (int) $numeroTreinos ?></li>
        <li><i class="fas fa-list-ol"></i> <strong>Treinos Necessários:</strong> <?= $treinosNecessarios ?></li>
        <li><i class="fas fa-hourglass-half"></i> <strong>Meses Decorridos:</strong> <?= $mesesDecorridos ?></li>
        <li><i class="fas fa-clock"></i> <strong>Tempo Mínimo (meses):</strong> <?= $tempoMinimoMeses ?></li>
    </ul>

    <p><a href="painel-aluno-editar.php" class="btn-edit">Editar meus dados</a></p>

    <script>
        const dojoLatitude = <?= json_encode($aluno['dojoLatitude']) ?>;
        const dojoLongitude = <?= json_encode($aluno['dojoLongitude']) ?>;
        const distanciaPermitida = <?= json_encode($aluno['distanciaPermitida']) ?>;
    </script>
</div>

<script src="../js/script.js"></script>
<?php include '../includes/footer.php'; ?>
