<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$tipo_usuario = $_SESSION['tipo_usuario'];

function buscarPresencas($pdo, $aluno_id) {
    $stmt = $pdo->prepare("
        SELECT data_presenca, COUNT(*) AS total
        FROM presencas
        WHERE aluno_id = ?
        GROUP BY data_presenca
    ");
    $stmt->execute([$aluno_id]);

    $resultado = [];
    foreach ($stmt->fetchAll() as $row) {
        $resultado[$row['data_presenca']] = (int)$row['total'];
    }

    return $resultado;
}

$alunos = [];

if ($tipo_usuario === 'sensei') {
    $stmt = $pdo->query("SELECT a.id AS aluno_id, u.nome FROM alunos a INNER JOIN usuarios u ON a.usuario_id = u.id
        ORDER BY u.nome ASC");
    while ($row = $stmt->fetch()) {
        $row['datas'] = buscarPresencas($pdo, $row['aluno_id']);
        $alunos[] = $row;
    }
} else {
    $stmt = $pdo->prepare("SELECT a.id AS aluno_id, u.nome FROM alunos a INNER JOIN usuarios u ON a.usuario_id = u.id WHERE u.id = ?");
    $stmt->execute([$usuario_id]);
    $aluno = $stmt->fetch();
    $aluno['datas'] = buscarPresencas($pdo, $aluno['aluno_id']);
    $alunos[] = $aluno;
}
?>

<?php include '../includes/header.php'; ?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<style>


.grid-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
}


.titulo {
    text-align: center;
    margin-bottom: 2rem;
}

.card-presenca {
    border: 1px solid #ddd;
    border-radius: 10px;
    padding: 15px;
    background-color: #f9f9f9;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    overflow: hidden;
}

.card-presenca h4 {
    text-align: center;
    margin-bottom: 1rem;
}

.flatpickr-day.presente {
    background-color: #28a745 !important;
    color: white !important;
    border-radius: 50% !important;
}

/* Modal básico */
.modal {
    display: none; /* escondido por padrão */
    position: fixed; 
    z-index: 1000; 
    left: 0; top: 0; width: 100%; height: 100%;
    overflow: auto; 
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background: white;
    margin: 10% auto;
    padding: 20px;
    border-radius: 6px;
    max-width: 400px;
    box-shadow: 0 5px 15px rgba(0,0,0,.3);
    position: relative;
}

.modal-close {
    position: absolute;
    right: 10px;
    top: 5px;
    font-size: 24px;
    font-weight: bold;
    cursor: pointer;
}

button {
    padding: 8px 14px;
    background-color: #4CAF50;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

button:hover {
    background-color: #45a049;
}
/* 1 presença no dia - verde */
.flatpickr-day.presente {
    background-color: #28a745 !important;
    color: white !important;
    border-radius: 50% !important;
}

/* 2 ou mais presenças no dia - metade verde / metade azul */
.flatpickr-day.presente-dupla {
    background: linear-gradient(
        90deg,
        #28a745 50%,
        #007bff 50%
    ) !important;
    color: white !important;
    border-radius: 50% !important;
}


@media (min-width: 1024px) {
    .grid-cards {
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    }
}

</style>

<div class="container">
    <h2 class="titulo">Presenças</h2>

    <div class="grid-cards">
        <?php foreach ($alunos as $aluno): ?>
            <div class="card-presenca">
                <h4><?= htmlspecialchars($aluno['nome']) ?></h4>
                <div id="calendario-<?= $aluno['aluno_id'] ?>" data-aluno-id="<?= $aluno['aluno_id'] ?>" data-aluno-nome="<?= htmlspecialchars($aluno['nome'], ENT_QUOTES) ?>"></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal -->
<div id="modalPresenca" class="modal">
  <div class="modal-content">
    <span class="modal-close" id="modalClose">&times;</span>
    <h3>Marcar Presença</h3>
    <p>Aluno: <strong id="modalAlunoNome"></strong></p>
    <p>Data selecionada: <strong id="modalDataSelecionada"></strong></p>
    <form id="formPresenca">
      <input type="hidden" name="aluno_id" id="modalAlunoId" />
      <input type="hidden" name="data_presenca" id="modalDataInput" />
      <button type="submit">Confirmar Presença</button>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js"></script>

<script>
const tipoUsuario = '<?= $tipo_usuario ?>';

// Modal elementos
const modal = document.getElementById('modalPresenca');
const modalClose = document.getElementById('modalClose');
const modalAlunoNome = document.getElementById('modalAlunoNome');
const modalDataSelecionada = document.getElementById('modalDataSelecionada');
const modalAlunoId = document.getElementById('modalAlunoId');
const modalDataInput = document.getElementById('modalDataInput');
const formPresenca = document.getElementById('formPresenca');

<?php foreach ($alunos as $aluno): ?>
flatpickr("#calendario-<?= $aluno['aluno_id'] ?>", {
    inline: true,
    locale: flatpickr.l10ns.pt,

    onDayCreate: function(dObj, dStr, fp, dayElem) {
        const dataFormatada = dayElem.dateObj.toISOString().slice(0,10);
        const presencas = <?= json_encode($aluno['datas']) ?>;

        if (presencas[dataFormatada]) {
            if (presencas[dataFormatada] === 1) {
                dayElem.classList.add("presente");
            } else if (presencas[dataFormatada] >= 2) {
                dayElem.classList.add("presente-dupla");
            }
        }
    },

    onChange: function(selectedDates, dateStr) {
        if (tipoUsuario !== 'sensei') {
            alert('Somente sensei pode marcar presença.');
            return;
        }

        if (!dateStr) return;

        modalAlunoNome.textContent = "<?= addslashes($aluno['nome']) ?>";
        modalDataSelecionada.textContent = dateStr;
        modalAlunoId.value = <?= $aluno['aluno_id'] ?>;
        modalDataInput.value = dateStr;

        modal.style.display = 'block';
    }
});
<?php endforeach; ?>


modalClose.onclick = () => {
    modal.style.display = 'none';
}

window.onclick = (event) => {
    if (event.target === modal) {
        modal.style.display = 'none';
    }
}

// Submissão do formulário - aqui você pode fazer envio via Ajax ou form normal
formPresenca.addEventListener('submit', function(e) {
    e.preventDefault();
    const alunoId = modalAlunoId.value;
    const dataPresenca = modalDataInput.value;
    
    // Aqui você deve enviar a requisição para o backend salvar no banco, exemplo com fetch (Ajax)
    fetch('marcar_presenca.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ aluno_id: alunoId, data_presenca: dataPresenca })
    })
    .then(res => res.json())
    .then(res => {
        if(res.success) {
            alert('Presença marcada com sucesso!');
            modal.style.display = 'none';
            // Atualizar a página ou calendário para refletir nova presença
            location.reload();
        } else {
            alert('Erro ao marcar presença: ' + res.error);
        }
    })
    .catch(() => alert('Erro na comunicação com o servidor.'));
});
</script>

<?php include '../includes/footer.php'; ?>
