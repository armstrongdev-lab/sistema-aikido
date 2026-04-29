<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/db.php';
include '../includes/header.php';

$token = $_GET['token'] ?? '';

$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE reset_token = ? AND reset_expira > NOW()");
$stmt->execute([$token]);
$usuario = $stmt->fetch();

if (!$usuario) {
    echo "<script>alert('Token inválido ou expirado.'); window.location.href = 'login.php';</script>";
    exit;
}
?>

<div class="login-container">
    <h2>Nova Senha</h2>
    <form action="processar-reset.php" method="POST" onsubmit="return validarSenhas();">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

        <div class="campo-senha">
            <input type="password" id="nova_senha" name="nova_senha" placeholder="Digite a nova senha" required>
            <span class="toggle-senha" onclick="toggleSenha('nova_senha', this)">👁️</span>
        </div>

        <div class="campo-senha">
            <input type="password" id="confirmar_senha" name="confirmar_senha" placeholder="Confirme a nova senha" required>
            <span class="toggle-senha" onclick="toggleSenha('confirmar_senha', this)">👁️</span>
        </div>

        <button type="submit">Redefinir Senha</button>
    </form>
</div>

<script>
function toggleSenha(idCampo, icone) {
    const campo = document.getElementById(idCampo);
    const isPassword = campo.type === "password";
    campo.type = isPassword ? "text" : "password";
    icone.textContent = isPassword ? "🙈" : "👁️"; // alterna o ícone
}

function validarSenhas() {
    const senha = document.getElementById("nova_senha").value;
    const confirmar = document.getElementById("confirmar_senha").value;

    if (senha !== confirmar) {
        alert("As senhas não coincidem. Por favor, digite novamente.");
        return false;
    }
    return true;
}
</script>

<style>
.login-container {
    max-width: 400px;
    margin: 50px auto;
    padding: 25px;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    font-family: Arial, sans-serif;
}

.campo-senha {
    position: relative;
    margin-bottom: 15px;
}

.campo-senha input {
    width: 100%;
    padding: 10px;
    padding-right: 40px; /* espaço pro ícone */
    border: 1px solid #ccc;
    border-radius: 5px;
    font-size: 15px;
    box-sizing: border-box;
}

.toggle-senha {
    position: absolute;
    right: 10px;
    top: 37%;
    transform: translateY(-50%);
    cursor: pointer;
    font-size: 18px;
    color: #666;
    user-select: none;
    transition: 0.3s;
}

.toggle-senha:hover {
    color: #000;
}

button[type="submit"] {
    width: 100%;
    padding: 10px;
    border: none;
    background: #007BFF;
    color: #fff;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
}

button[type="submit"]:hover {
    background: #0056b3;
}
</style>

<?php include '../includes/footer.php'; ?>
