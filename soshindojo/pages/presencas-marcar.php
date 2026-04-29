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

// Permitir acesso para sensei ou admin
if (!in_array($_SESSION['tipo_usuario'], ['sensei', 'admin'])) {
    header('Location: ../index.php');
    exit;
}

// Buscar todos os dojos para o filtro (apenas admin precisa)
// Todos os usuários podem buscar, mas só admin verá o filtro
$stmtDojos = $pdo->prepare("SELECT id, nome FROM dojos ORDER BY nome ASC");
$stmtDojos->execute();
$dojos = $stmtDojos->fetchAll(PDO::FETCH_ASSOC);

$tipo_usuario = $_SESSION['tipo_usuario'];

if ($tipo_usuario === 'sensei') {
    // Sensei usa seu dojo fixo
    $dojo_id = $_SESSION['dojo_id'] ?? null;
    if (!$dojo_id) {
        echo "Dojo não definido para o sensei logado.";
        exit;
    }
} else {
    // Admin pode escolher o dojo via filtro GET
    // Se não selecionar, mostra todos os alunos e treinos (dojo_id null)
    $dojo_id = isset($_GET['dojo_id']) && $_GET['dojo_id'] !== '' ? (int) $_GET['dojo_id'] : null;
}

// Buscar alunos e treinos conforme filtro

if ($dojo_id) {
    // Filtrar por dojo_id
    $sql = "SELECT a.id AS aluno_id, u.nome 
        FROM alunos a 
        JOIN usuarios u ON a.usuario_id = u.id 
        WHERE a.dojo_id = :dojo_id
        AND u.ativo = 1
        ORDER BY u.nome ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['dojo_id' => $dojo_id]);

    $stmtTreinos = $pdo->prepare("SELECT id, dia_semana, horario FROM treinos_horarios WHERE dojo_id = :dojo_id ORDER BY dia_semana, horario");
    $stmtTreinos->execute(['dojo_id' => $dojo_id]);
} else {
    // Sem filtro: traz todos os alunos e treinos (admin)
    $sql = "SELECT a.id AS aluno_id, u.nome 
            FROM alunos a 
            JOIN usuarios u ON a.usuario_id = u.id 
            ORDER BY u.nome ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $stmtTreinos = $pdo->prepare("SELECT id, dia_semana, horario FROM treinos_horarios ORDER BY dia_semana, horario");
    $stmtTreinos->execute();
}

$alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);
$treinos = $stmtTreinos->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container presenca-container">

<style>
    form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    max-width: 100%;
    margin: auto;
    text-align: left;
}    
</style>
    <h1 class="titulo-presenca">Marcar Presença</h1>

    <?php if ($tipo_usuario === 'admin'): ?>
        <!-- FILTRO DE DOJO PARA ADMIN -->
        <form method="GET" action="">
            <label for="dojo_id">Filtrar por Dojo:</label>
            <select name="dojo_id" id="dojo_id" onchange="this.form.submit()">
                <option value="">Todos os Dojos</option>
                <?php foreach ($dojos as $dojo): ?>
                    <option value="<?= $dojo['id'] ?>" <?= ($dojo_id == $dojo['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($dojo['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <noscript><button type="submit">Filtrar</button></noscript>
        </form>
        <hr>
    <?php endif; ?>

    <?php if (isset($_GET['sucesso']) && $_GET['sucesso'] == 1): ?>
        <?php $qtd = intval($_GET['inseridas'] ?? 0); ?>
        <div class="mensagem-sucesso"
            style="background-color: #d4edda; color: #155724; padding: 10px; border: 1px solid #c3e6cb; border-radius: 5px; margin-bottom: 15px;">
            <?php if ($qtd > 0): ?>
                ✅ <?= $qtd ?> presença(s) registrada(s) com sucesso!
            <?php else: ?>
                ⚠️ Nenhuma presença registrada (todos os alunos já estavam marcados para esta data).
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <form action="presencas-marcar-process.php" method="POST">
        <label for="data_presenca">Data da Presença:</label>
        <input type="date" id="data_presenca" name="data_presenca" required class="input-data">

        <fieldset class="fieldset-treino">
            <legend>Selecione o treino (dia/horário):</legend>
            <?php foreach ($treinos as $treino): ?>
                <label class='label-radio'>
                    <input type='radio' name='treino_id' value='<?= $treino['id'] ?>' required>
                    <?= htmlspecialchars($treino['dia_semana']) ?> - <?= htmlspecialchars($treino['horario']) ?>
                </label><br>
            <?php endforeach; ?>
        </fieldset>

        <fieldset class="fieldset-alunos">
            <legend>Marcar presença dos alunos:</legend>
            <button type="button" id="marcar-todos" class="btn" style="margin-bottom: 12px;">Marcar Todos</button>
            <?php foreach ($alunos as $aluno): ?>
                <label class="label-checkbox-nome">
                    <input type="checkbox" name="alunos[]" value="<?= $aluno['aluno_id'] ?>" class="checkbox-aluno">
                    <?= htmlspecialchars($aluno['nome']) ?>
                </label><br>
            <?php endforeach; ?>
        </fieldset>

        <?php if ($tipo_usuario === 'admin'): ?>
            <!-- Envia dojo_id selecionado no filtro para o process.php -->
            <input type="hidden" name="dojo_id" value="<?= htmlspecialchars($dojo_id) ?>">
        <?php endif; ?>

        <button type="submit" class="btn">Registrar Presença</button>
    </form>

</div>

<script>
    // Alternar marcação de todos os checkboxes
    const btnToggle = document.getElementById('marcar-todos');
    const checkboxes = document.querySelectorAll('.checkbox-aluno');

    btnToggle.addEventListener('click', function () {
        const allChecked = [...checkboxes].every(cb => cb.checked);
        checkboxes.forEach(cb => cb.checked = !allChecked);
        this.textContent = allChecked ? 'Marcar Todos' : 'Desmarcar Todos';
    });

    // Oculta a mensagem de sucesso após 5 segundos
    setTimeout(() => {
        const msg = document.querySelector('.mensagem-sucesso');
        if (msg) {
            msg.style.transition = 'opacity 0.5s ease-out';
            msg.style.opacity = '0';
            setTimeout(() => msg.remove(), 500); // remove do DOM após desaparecer
        }
    }, 5000);
</script>

<?php include '../includes/footer.php'; ?>