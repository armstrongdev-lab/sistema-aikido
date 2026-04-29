<?php
session_start();
include '../includes/header.php';
$fromSensei = (isset($_GET['from']) && $_GET['from'] === 'sensei');
?>

<div class="login-container">
    <style>
        .mensagem-erro {
    background-color: #ffdddd;
    color: #a94442;
    padding: 12px;
    border: 1px solid #f5c6cb;
    border-radius: 5px;
    margin-bottom: 20px;
    font-size: 0.95rem;
    text-align: center;
    animation: fadeIn 0.4s ease-in-out;
}

    form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    max-width: 100%;
    margin: auto;
    text-align: left;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

    </style>
    <h2>Cadastro de Aluno</h2>
    <form id="formCadastroAluno" method="POST">
        <input type="text" name="nome" placeholder="Nome completo" required>
        <input type="email" name="email" placeholder="E-mail" required>
        <input type="password" name="senha" placeholder="Senha" required>
        <input type="password" name="confirmar_senha" id="confirmar_senha" placeholder="Repita a senha" required>
        <input type="text" name="telefone" id="telefone" placeholder="Telefone" required>
        <input type="text" name="endereco" placeholder="Endereço" required>

        <div class="form-group-inline">
            <label for="dojo">Selecione o Dojo:</label>
            <select name="dojo_id" id="dojo" required>
                <option value="">Selecione...</option>
                <?php
                require_once '../includes/db.php';
                $res = $pdo->query("SELECT id, nome FROM dojos ORDER BY nome");
                while ($dojo = $res->fetch(PDO::FETCH_ASSOC)) {
                    echo "<option value='{$dojo['id']}'>{$dojo['nome']}</option>";
                }
                ?>
            </select>

        </div>

        <label>Data de nascimento:</label>
        <input type="date" name="data_nascimento" required>

        <label>Data de início do treino:</label>
        <input type="date" name="data_inicio" required>

        <?php if ($fromSensei): ?>
            <input type="hidden" name="from" value="sensei">
        <?php endif; ?>

        <button type="submit">Cadastrar</button>
        <div id="mensagemErro" class="mensagem-erro" style="display: none;"></div>
    </form>
    

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Máscara do telefone
            const telefoneInput = document.getElementById('telefone');
            telefoneInput.addEventListener('input', function () {
                let valor = telefoneInput.value.replace(/\D/g, '');
                if (valor.length > 11) valor = valor.slice(0, 11);

                if (valor.length > 10) {
                    valor = valor.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
                } else if (valor.length > 6) {
                    valor = valor.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
                } else if (valor.length > 2) {
                    valor = valor.replace(/(\d{2})(\d{0,5})/, '($1) $2');
                } else {
                    valor = valor.replace(/(\d*)/, '($1');
                }

                telefoneInput.value = valor;
            });

            // Envio do formulário via fetch com validação de senha
            const form = document.getElementById('formCadastroAluno');
            form.addEventListener('submit', async function (e) {
                e.preventDefault();

                const senha = form.querySelector('input[name="senha"]').value;
                const confirmarSenha = form.querySelector('input[name="confirmar_senha"]').value;

                const erroDiv = document.getElementById('mensagemErro');
if (senha !== confirmarSenha) {
    erroDiv.textContent = 'As senhas não coincidem.';
    erroDiv.style.display = 'block';
    return;
} else {
    erroDiv.style.display = 'none';
}


                const formData = new FormData(form);

                try {
                    const response = await fetch('register-process.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.success) {
                        window.location.href = result.redirect;
                    } else {
                        alert(result.message || 'Erro ao cadastrar.');
                    }
                } catch (err) {
                    alert('Erro inesperado. Verifique a conexão com o servidor.');
                    console.error(err);
                }
            });

        });
    </script>
</div>

<?php include '../includes/footer.php'; ?>