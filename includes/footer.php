<!-- Estilo CSS -->
<style>
/* Reset básico */
body {
    margin: 0;
    padding: 0;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

/* Conteúdo principal ocupa o espaço disponível */
.main-content {
    flex: 1;
}

/* Rodapé normal no final da página */
.main-footer {
    background-color: #f2f2f2;
    color: #333;
    text-align: center;
    /*padding: 1rem 0;*/
    margin-top: 2rem;
}

/* Botão WhatsApp flutuante */
.whatsapp-fixo {
    position: fixed;
    bottom: 20px;
    right: 10px;
    background-color: #25D366;
    border-radius: 50%;
    padding: 15px;
    z-index: 999;
    text-decoration: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
}

.whatsapp-fixo img {
    width: 25px;
    height: 25px;
}

.whatsapp-fixo:hover {
    background-color: #1ebe5d;
}
</style>

<!-- Corpo da página -->
<div class="main-content">
    <!-- Aqui entra o conteúdo principal da página -->
</div>

<!-- Botão flutuante do WhatsApp -->
<a class="whatsapp-fixo" href="https://wa.me/5542991179118" target="_blank" title="Fale conosco pelo WhatsApp">
    <img src="/assets/img/whatsapp-icon.png" alt="WhatsApp">
</a>

<!-- Rodapé normal -->
<footer class="main-footer">
    <div class="container">
        <p>© 2025 Aikido Soshin Dojo - Todos os direitos reservados</p>
    </div>
</footer>
