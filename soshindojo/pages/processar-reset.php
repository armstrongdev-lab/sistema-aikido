<?php
include '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'];
    $novaSenha = password_hash($_POST['nova_senha'], PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE reset_token = ? AND reset_expira > NOW()");
    $stmt->execute([$token]);
    $usuario = $stmt->fetch();

    if ($usuario) {
        $stmt = $pdo->prepare("UPDATE usuarios SET senha = ?, reset_token = NULL, reset_expira = NULL WHERE id = ?");
        $stmt->execute([$novaSenha, $usuario['id']]);

        echo "<script>alert('Senha atualizada com sucesso!'); window.location.href = 'login.php';</script>";
    } else {
        echo "<script>alert('Token inválido ou expirado.'); window.location.href = 'login.php';</script>";
    }
}
?>
