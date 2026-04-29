<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['usuario_id'])) {
  header('Location: login.php');
  exit;
}

if ($_SESSION['tipo_usuario'] !== 'sensei') {
  header("Location: login.php");
  exit;
}

if (!isset($_SESSION['dojo_id'])) {
  echo "Dojo não definido para este usuário.";
  exit;
}

$dojo_id = $_SESSION['dojo_id'];

require_once '../includes/db.php';

$stmt = $pdo->prepare("SELECT * FROM dojos WHERE id = ?");
$stmt->execute([$dojo_id]);
$dojo = $stmt->fetch();

if (!$dojo) {
  echo "Dojo inválido.";
  exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <title>Painel do Sensei - <?php echo htmlspecialchars($dojo['nome']); ?></title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" href="../assets/img/favicon-padrao.ico" type="image/x-icon">
</head>

<body>

  <?php include '../includes/header.php'; ?>

  <main class="main-content">
    <div class="painel-aluno">
      <h2>Bem-vindo, Sensei do dojo <?php echo htmlspecialchars($dojo['nome']); ?></h2>
      <p>Escolha uma das opções no menu acima para gerenciar os alunos, presenças e exames.</p>

      <div class="atalhos">
        <a class="btn-edit" href="presencas-marcar.php"><span style="color:green;">✔️</span> Marcar Presenças</a>
        <a class="btn-edit" href="presencas.php"><span style="color:green;">🗓️</span> Calendario Presenças</a>
        <a class="btn-edit" href="alunos-listar.php">📋 Ver Alunos</a>
        <a class="btn-edit" href="register.php">➕ Novo Aluno</a>
        <a class="btn-edit" href="exames-gerenciar.php">🥋 Exames</a>
        <a href="#" id="btnTreinosCadastrar" class="btn-edit">➕ Cadastrar Treinos</a>
        <a class="btn-edit" href="mensalidades-lancar.php">💰 Lançar Mensalidades</a>
        <a class="btn-edit" href="mensalidades-gerenciar.php">📊 Gerenciar Mensalidades</a>
        <a href="#" id="btnUsuariosAprovar" class="btn-edit">✅ Aprovar Usuários</a>
      </div>

    </div>
  </main>

  <?php include '../includes/footer.php'; ?>

  <style>
    .modal {
      position: fixed;
      z-index: 9999;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background-color: rgba(0, 0, 0, 0.6);
      display: none;
      align-items: center;
      justify-content: center;
    }

    .modal.show {
      display: flex;
    }
  </style>

  <!-- Modal Senha Admin -->
  <div id="modalSenhaAdm" class="modal">
    <div class="modal-content"
      style="max-width: 400px; margin: auto; padding: 20px; background: white; border-radius: 8px;">
      <span id="closeModal" style="float:right;cursor:pointer;font-size:20px;">&times;</span>
      <h3>Digite a senha especial do administrador</h3>
      <form id="formSenhaAdm">
        <input type="password" id="senhaAdmInput" name="senha_adm" placeholder="Senha especial" required
          style="width: 100%; padding: 8px; margin-top:10px;">
        <div id="erroSenhaAdm" style="color:red; margin-top:10px; display:none;"></div>
        <button type="submit" style="margin-top:15px; padding: 10px 15px;">Validar</button>
      </form>
    </div>
  </div>

  <script>
    document.addEventListener("DOMContentLoaded", () => {
      const btnAbrir = document.getElementById('btnUsuariosAprovar');
      const modal = document.getElementById('modalSenhaAdm');
      const btnFechar = document.getElementById('closeModal');
      const form = document.getElementById('formSenhaAdm');
      const inputSenha = document.getElementById('senhaAdmInput');
      const erroDiv = document.getElementById('erroSenhaAdm');

      btnAbrir.addEventListener('click', e => {
        e.preventDefault();
        modal.classList.add('show');
      });

      btnFechar.addEventListener('click', () => {
        modal.classList.remove('show');
        erroDiv.style.display = 'none';
        inputSenha.value = '';
      });

      window.addEventListener('click', e => {
        if (e.target === modal) {
          modal.classList.remove('show');
          erroDiv.style.display = 'none';
          inputSenha.value = '';
        }
      });

      form.addEventListener('submit', function (e) {
        e.preventDefault();
        const senha = inputSenha.value.trim();

        fetch('verificar-senha.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: 'senha_adm=' + encodeURIComponent(senha)
        })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              modal.classList.remove('show');
              window.location.href = 'usuarios-aprovar.php';
            } else {
              erroDiv.style.display = 'block';
              erroDiv.textContent = data.msg || 'Erro de autenticação.';
            }
          })
          .catch(() => {
            erroDiv.style.display = 'block';
            erroDiv.textContent = 'Erro na comunicação com o servidor.';
          });
      });
    });

  </script>


</body>

</html>