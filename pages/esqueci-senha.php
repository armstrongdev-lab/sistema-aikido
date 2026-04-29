<?php include '../includes/header.php'; ?>

<div class="login-container">
    <h2>Recuperar Senha</h2>
    <form action="enviar-reset.php" method="POST">
        <input type="email" name="email" placeholder="Digite seu e-mail" required>
        <button type="submit">Enviar link de redefinição</button>
    </form>
    <p><a href="login.php">Voltar ao login</a></p>
</div>

<?php include '../includes/footer.php'; ?>
