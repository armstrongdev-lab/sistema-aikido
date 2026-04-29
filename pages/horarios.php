<?php
require_once('../includes/header.php');
require_once('../includes/db.php');   // conexão PDO

// Buscar lista de dojos para o select
$stmtDojo = $pdo->query("SELECT id, nome FROM dojos ORDER BY nome");
$dojos = $stmtDojo->fetchAll();

// Recebe dojo selecionado no formulário
$dojo_id = $_GET['dojo_id'] ?? null;

$horarios = [];
if ($dojo_id) {
    // Buscar horários do dojo selecionado
    $stmtHorarios = $pdo->prepare("SELECT dia_semana, horario FROM treinos_horarios WHERE dojo_id = ? ORDER BY FIELD(dia_semana, 'Domingo','Segunda','Terça','Quarta','Quinta','Sexta','Sábado'), horario");
    $stmtHorarios->execute([$dojo_id]);
    $horarios = $stmtHorarios->fetchAll();
}

function traduzirDia($dia) {
    $dias = [
        'Domingo' => 'Domingo',
        'Segunda' => 'Segunda-feira',
        'Terça'   => 'Terça-feira',
        'Quarta'  => 'Quarta-feira',
        'Quinta'  => 'Quinta-feira',
        'Sexta'   => 'Sexta-feira',
        'Sábado'  => 'Sábado',
    ];
    return $dias[$dia] ?? $dia;
}
?>

<!-- Conteúdo da página começa aqui -->
<main style="padding: 20px; max-width: 700px; margin: auto;">

    <form method="get" action="" style="margin-bottom: 30px; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 3px 8px rgba(0,0,0,0.1);">
        <label for="dojo_id" style="font-weight: 600; font-size: 1.1rem; display: block; margin-bottom: 10px;">Selecione o Dojo:</label>
        <select name="dojo_id" id="dojo_id" onchange="this.form.submit()" style="width: 100%; padding: 10px 15px; font-size: 1rem; border-radius: 6px; border: 1px solid #ccc; cursor: pointer;">
            <option value="">-- Escolha um Dojo --</option>
            <?php foreach ($dojos as $dojo): ?>
                <option value="<?= htmlspecialchars($dojo['id']) ?>" <?= ($dojo['id'] == $dojo_id) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($dojo['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php if ($dojo_id): ?>
        <h2 style="text-align: center; color: #444; margin-bottom: 15px; font-weight: 700;">
            Horários do Dojo: <?= htmlspecialchars($dojos[array_search($dojo_id, array_column($dojos, 'id'))]['nome'] ?? '') ?>
        </h2>

        <?php if ($horarios): ?>
            <ul style="list-style: none; padding: 0;">
                <?php foreach ($horarios as $hora): ?>
                    <li style="background: #fff; margin-bottom: 10px; padding: 12px 18px; border-radius: 6px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); font-size: 1rem; color: #222;">
                        <?= traduzirDia($hora['dia_semana']) ?> às <?= substr($hora['horario'], 0, 5) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p style="text-align:center; color:#666;">Nenhum horário cadastrado para este dojo.</p>
        <?php endif; ?>
    <?php endif; ?>

</main>

<?php require_once('../includes/footer.php'); ?>
