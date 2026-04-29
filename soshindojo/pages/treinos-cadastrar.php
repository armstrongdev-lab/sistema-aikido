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

// Verifica se o usuário é sensei ou admin
if (!in_array($_SESSION['tipo_usuario'], ['sensei', 'admin'])) {
    header('Location: ../index.php');
    exit;
}

$sensei_id = $_SESSION['usuario_id'];
$tipo_usuario = $_SESSION['tipo_usuario'];

// Define dojo_id
if ($tipo_usuario === 'admin' && isset($_POST['dojo_id']) && is_numeric($_POST['dojo_id'])) {
    $dojo_id = $_POST['dojo_id'];
} else {
    $stmtDojo = $pdo->prepare("SELECT dojo_id FROM usuarios WHERE id = ?");
    $stmtDojo->execute([$sensei_id]);
    $dojo_id = $stmtDojo->fetchColumn();
}

// Carregar todos os dojos se admin
$dojos = [];
if ($tipo_usuario === 'admin') {
    $stmtDojos = $pdo->query("SELECT id, nome FROM dojos ORDER BY nome");
    $dojos = $stmtDojos->fetchAll(PDO::FETCH_ASSOC);
}

// Dias e horários
$diasSemana = ['Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado', 'Domingo'];
$horarios = [];
for ($h = 6; $h <= 22; $h++) {
    $horarios[] = sprintf('%02d:00', $h);
}

// Lógica do filtro
if ($tipo_usuario === 'admin' && isset($_POST['filtro_dojo_id'])) {
    $_SESSION['filtro_dojo_id'] = $_POST['filtro_dojo_id'];
}

if (isset($_SESSION['filtro_dojo_id']) && is_numeric($_SESSION['filtro_dojo_id'])) {
    $dojo_id = $_SESSION['filtro_dojo_id'];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
    
        <?php include '../includes/header.php'; ?>

<head>
    <meta charset="UTF-8">
    <title>Cadastrar Treinos - Painel Sensei</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .msg-sucesso {
            background-color: #d4edda;
            color: #155724;
            padding: 10px 15px;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
        }

        .msg-erro {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px 15px;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
        }

        .filtro-dojo-container {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            background-color: #f9f9f9;
            padding: 15px 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 30px;
            max-width: 600px;
        }

        .filtro-dojo-container label {
            font-weight: bold;
            white-space: nowrap;
            margin-right: 10px;
        }

        .filtro-dojo-container select {
            flex-grow: 1;
            min-width: 180px;
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
        }

        .filtro-dojo-container button {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            background-color: #007bff;
            color: white;
            white-space: nowrap;
            transition: background-color 0.3s ease;
        }

        .filtro-dojo-container button:hover {
            background-color: #0056b3;
        }

        /* Responsividade para telas pequenas */
        @media (max-width: 480px) {
            .filtro-dojo-container {
                flex-direction: column;
                align-items: stretch;
            }

            .filtro-dojo-container label,
            .filtro-dojo-container select,
            .filtro-dojo-container button {
                width: 100%;
                margin-right: 0;
                margin-top: 8px;
            }

            .filtro-dojo-container label {
                margin-top: 0;
            }
        }

        /* Dias da semana e horários em 2 colunas */
        .form-treinos {
    display: flex;
    flex-wrap: wrap;
    gap: 30px;
    margin-bottom: 30px;
}

.form-treinos fieldset {
    flex: 1;
    min-width: 250px;
}

.field-dias label,
.field-horarios label {
    display: block;
    margin-bottom: 8px;
}

        fieldset {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    border: 1px solid #ccc;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 30px;
}

fieldset legend {
    width: 100%;
    font-weight: bold;
    margin-bottom: 10px;
}

.field-dias label {
    width: 100%; /* Uma coluna */
    margin-bottom: 8px;
}

.field-horarios {
    display: grid;
    grid-template-columns: 1fr 1fr; /* Duas colunas */
    gap: 10px;
    width: 100%;
}

.field-horarios label {
    margin-bottom: 8px;
}

        

        /* Media query removida para manter 2 colunas em telas pequenas */
    </style>
</head>

<body>

    <div class="container">
        <h2>Cadastrar Horários de Treino</h2>

        <?php
        if (isset($_GET['msg']) && $_GET['msg'] === 'treinos_cadastrados') {
            echo "<p class='msg-sucesso'>Treinos cadastrados com sucesso!</p>";
        }
        if (isset($_GET['erro']) && $_GET['erro'] === 'selecionar_dia_horario') {
            echo "<p class='msg-erro'>Por favor, selecione ao menos um dia e um horário.</p>";
        }
        ?>

        <?php if ($tipo_usuario === 'admin'): ?>
            <form method="post" class="filtro-dojo-container">
                <label for="filtro_dojo_id">Filtrar por Dojo:</label>
                <select name="filtro_dojo_id" id="filtro_dojo_id">
                    <option value="">-- Todos os dojos --</option>
                    <?php foreach ($dojos as $dojo): ?>
                        <option value="<?= $dojo['id'] ?>" <?= (isset($_SESSION['filtro_dojo_id']) && $_SESSION['filtro_dojo_id'] == $dojo['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dojo['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">Filtrar</button>
            </form>
        <?php endif; ?>

        <form action="treinos-cadastrar-process.php" method="POST">
            <fieldset>
                <legend>Selecione os dias da semana:</legend>
                <?php foreach ($diasSemana as $dia): ?>
                    <label>
                        <input type="checkbox" name="dias[]" value="<?= $dia ?>"> <?= $dia ?>
                    </label>
                <?php endforeach; ?>
            </fieldset>

            <fieldset>
                <legend>Selecione os horários:</legend>
                <?php foreach ($horarios as $hora): ?>
                    <label>
                        <input type="checkbox" name="horarios[]" value="<?= $hora ?>"> <?= $hora ?>
                    </label>
                <?php endforeach; ?>
            </fieldset>

            <input type="hidden" name="dojo_id" value="<?= $dojo_id ?>">
            <button type="submit">Salvar Treinos</button>
        </form>

        <br>
        <hr><br>

        <h3>Treinos já cadastrados</h3>

        <table>
            <thead>
                <tr>
                    <th>Dia da Semana</th>
                    <th>Horário</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmtTreinos = $pdo->prepare("SELECT id, dia_semana, horario FROM treinos_horarios WHERE dojo_id = ? ORDER BY FIELD(dia_semana, 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado', 'Domingo'), horario");
                $stmtTreinos->execute([$dojo_id]);
                while ($treino = $stmtTreinos->fetch()):
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($treino['dia_semana']) ?></td>
                        <td><?= htmlspecialchars(substr($treino['horario'], 0, 5)) ?></td>
                        <td>
                            <form action="treinos-excluir.php" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este treino?');">
                                <input type="hidden" name="treino_id" value="<?= $treino['id'] ?>">
                                <button type="submit" class="btn-danger">Excluir</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <?php include '../includes/footer.php'; ?>

</body>

</html>
