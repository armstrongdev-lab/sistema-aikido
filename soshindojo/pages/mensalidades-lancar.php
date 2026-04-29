<?php
include '../includes/header.php';
require_once '../includes/db.php';

// Apenas sensei e admin podem acessar
if ($_SESSION['tipo_usuario'] !== 'sensei' && $_SESSION['tipo_usuario'] !== 'admin') {
    echo "<p>Acesso negado.</p>";
    include '../includes/footer.php';
    exit;
}

// Valor padrão da mensalidade
$valor_mensalidade = 110.00;
$mensagem = "";

// Ao clicar no botão
if (isset($_POST['gerar'])) {
    $mesAno = date('Y-m'); // Ex: 2025-07

    // Verifica se já existem mensalidades para esse mês
    $verifica = $pdo->prepare("SELECT COUNT(*) FROM mensalidades WHERE mes_ano = ?");
    $verifica->execute([$mesAno]);
    $existe = $verifica->fetchColumn();

    if ($existe > 0) {
        $mensagem = "<p style='color:orange;'>Mensalidades de $mesAno já foram lançadas.</p>";
    } else {
        // Busca todos os alunos e os dados do usuário vinculado
        $alunos = $pdo->query("
            SELECT a.id, u.tipo_usuario 
            FROM alunos a 
            JOIN usuarios u ON a.usuario_id = u.id
        ")->fetchAll();

        $stmt = $pdo->prepare("INSERT INTO mensalidades (aluno_id, mes_ano, valor, status) VALUES (?, ?, ?, 'Em Aberto')");
        $contador = 0;

        foreach ($alunos as $aluno) {
            // Pula o aluno ID 39 se ele for sensei
            if ($aluno['id'] == 39 && $aluno['tipo_usuario'] === 'sensei') {
                continue;
            }

            $stmt->execute([$aluno['id'], $mesAno, $valor_mensalidade]);
            $contador++;
        }

        $mensagem = "<p style='color:green;'>Mensalidades de $mesAno lançadas com sucesso para $contador alunos.</p>";
    }
}
?>

<div class="container painel-sensei">
    <h2>Lançar Mensalidades</h2>

    <?= $mensagem ?>

    <form method="POST">
        <p>Ao clicar abaixo, as mensalidades do mês <strong><?= date('m/Y') ?></strong> serão lançadas para todos os alunos ativos (exceto sensei).</p>
        <button type="submit" name="gerar" class="btn">Lançar Mensalidades do Mês</button>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
