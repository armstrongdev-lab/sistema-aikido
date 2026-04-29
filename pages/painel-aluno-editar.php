<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php'); // ajuste o caminho se necessário
    exit;
}

require_once '../includes/db.php';

$usuario_id = $_SESSION['usuario_id'];

// Buscar dados do aluno com o nome do dojo
$stmt = $pdo->prepare("
    SELECT 
        u.nome, 
        u.email, 
        a.telefone, 
        a.endereco, 
        a.dojo_id,
        d.nome AS dojo_nome, 
        a.data_nascimento, 
        a.data_inicio_treinos, 
        a.graduacao, 
        a.numero_treinos
    FROM usuarios u
    INNER JOIN alunos a ON u.id = a.usuario_id
    LEFT JOIN dojos d ON a.dojo_id = d.id
    WHERE u.id = ?
");
$stmt->execute([$usuario_id]);
$aluno = $stmt->fetch();

if (!$aluno) {
    echo "Aluno não encontrado.";
    exit;
}

// Buscar lista de dojos para popular o select
$stmtDojos = $pdo->query("SELECT id, nome FROM dojos ORDER BY nome");
$listaDojos = $stmtDojos->fetchAll();

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'] ?? '';
    $email = $_POST['email'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $endereco = $_POST['endereco'] ?? '';
    $dojo_id = $_POST['dojo_id'] ?? null;
    $data_nascimento = $_POST['data_nascimento'] ?? '';
    $data_inicio = $_POST['data_inicio'] ?? '';

    // Validações básicas
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = "E-mail inválido.";
    } elseif (empty($nome) || empty($telefone) || empty($endereco) || empty($dojo_id) || empty($data_nascimento) || empty($data_inicio)) {
        $erro = "Preencha todos os campos obrigatórios.";
    } else {
        try {
            $pdo->beginTransaction();

            // Atualiza tabela usuarios
            $stmtUser = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ? WHERE id = ?");
            $stmtUser->execute([$nome, $email, $usuario_id]);

            // Atualiza tabela alunos (dojo_id)
            $stmtAluno = $pdo->prepare("UPDATE alunos SET telefone = ?, endereco = ?, dojo_id = ?, data_nascimento = ?, data_inicio_treinos = ? WHERE usuario_id = ?");
            $stmtAluno->execute([$telefone, $endereco, $dojo_id, $data_nascimento, $data_inicio, $usuario_id]);

            $pdo->commit();

            // Recarregar os dados atualizados
            $stmt->execute([$usuario_id]);
            $aluno = $stmt->fetch();

            $sucesso = "Dados atualizados com sucesso.";

            // Redireciona para painel-aluno após salvar alterações (se preferir)
            // header('Location: painel-aluno.php');
            // exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $erro = "Erro ao atualizar dados: " . $e->getMessage();
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="container painel-aluno">
    <h2>Editar meus dados</h2>

    <?php if (isset($erro)): ?>
        <p style="color: red;"><?= htmlspecialchars($erro) ?></p>
    <?php endif; ?>

    <?php if (isset($sucesso)): ?>
        <p style="color: green;"><?= htmlspecialchars($sucesso) ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <label>Nome completo:</label>
        <input type="text" name="nome" value="<?= htmlspecialchars($aluno['nome']) ?>" required>

        <label>E-mail:</label>
        <input type="email" name="email" value="<?= htmlspecialchars($aluno['email']) ?>" required>

        <label>Telefone:</label>
        <input type="text" name="telefone" value="<?= htmlspecialchars($aluno['telefone']) ?>" required>

        <label>Endereço:</label>
        <input type="text" name="endereco" value="<?= htmlspecialchars($aluno['endereco']) ?>" required>

        <label for="dojo_id">Selecione o Dojo:</label>
        <select name="dojo_id" id="dojo_id" required>
            <option value="">Selecione...</option>
            <?php foreach ($listaDojos as $dojo): ?>
                <option value="<?= $dojo['id'] ?>" <?= $aluno['dojo_id'] == $dojo['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($dojo['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Data de nascimento:</label>
        <input type="date" name="data_nascimento" value="<?= htmlspecialchars($aluno['data_nascimento']) ?>" required>

        <label>Data de início do treino:</label>
        <input type="date" value="<?= htmlspecialchars($aluno['data_inicio_treinos']) ?>" disabled>
        <input type="hidden" name="data_inicio" value="<?= htmlspecialchars($aluno['data_inicio_treinos']) ?>">

        <label>Graduação atual:</label>
        <input type="text" value="<?= htmlspecialchars($aluno['graduacao']) ?>" disabled>

        <label>Número de treinos:</label>
        <input type="number" value="<?= (int) $aluno['numero_treinos'] ?>" disabled>

        <button type="submit">Salvar alterações</button>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
