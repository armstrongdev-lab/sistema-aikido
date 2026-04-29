<?php
require_once __DIR__ . '/includes/header.php'; // reutiliza seu header dinâmico
?>
<style>
    .hero {
        position: relative;
        width: 100%;
        overflow: hidden;
    }

    .publico section:not(.hero) {
        padding: 4rem 64px 0px;
        text-align: center;
    }

    .hero {
        padding: 0 !important;
        margin-top: 1px;
    }

    .hero-img {
        width: 100%;
        height: auto;
        display: block;
    }

    /* Botão WhatsApp */
    .hero .whatsapp-btn {
        position: absolute;
        top: 20px;
        right: 20px;
        display: flex;
        align-items: center;
        background-color: #25D366;
        padding: 10px 15px;
        border-radius: 30px;
        text-decoration: none;
        color: white;
        font-weight: bold;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        z-index: 10;
        transition: all 0.3s ease;
    }

    .hero .whatsapp-btn img {
        width: 24px;
        height: 24px;
        margin-right: 10px;
    }

    .imagem-destaque,
    .grid-galeria img {
        max-width: 100%;
        height: auto;
        display: block;
        margin: 20px auto;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        /* sombra leve para destacar */
    }

    /* Galeria em grid flexível */
    .grid-galeria {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 20px;
        margin-top: 20px;
    }

    .grid-galeria img {
        max-width: 300px;
        width: 100%;
        border-radius: 10px;
        cursor: zoom-in;
        transition: transform 0.3s ease;
    }

    .grid-galeria img:hover {
        transform: scale(1.05);
    }

    /* Imagem ampliável (única destaque e galeria) */
    .imagem-ampliavel {
        cursor: zoom-in;
        transition: transform 0.3s ease;
    }

    .imagem-ampliavel:hover {
        transform: scale(1.02);
    }

    /* Lightbox */
    .lightbox {
        display: none;
        position: fixed;
        z-index: 9999;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background-color: rgba(0, 0, 0, 0.9);
        justify-content: center;
        align-items: center;
    }

    .lightbox-content {
        max-width: 90%;
        max-height: 90%;
        border-radius: 8px;
    }

    .lightbox .close {
        position: absolute;
        top: 30px;
        right: 40px;
        font-size: 40px;
        color: #fff;
        cursor: pointer;
    }

    .sobre-o-dojo{
        margin: 10px auto;
    }

    /* Linha do tempo */
    .linha-do-tempo {
        list-style: none;
        padding: 0;
        max-width: 600px;
        margin: 10px auto;
        text-align: left;
    }

    .linha-do-tempo li {
        position: relative;
        padding-left: 20px;
        margin-bottom: 10px;
    }

    .linha-do-tempo li::before {
        content: "●";
        position: absolute;
        left: 0;
        color: #25D366;
    }

    /* Formulário */
    form input,
    form textarea {
        width: 100%;
        max-width: 500px;
        padding: 12px;
        margin-bottom: 12px;
        border: 1px solid #ccc;
        border-radius: 8px;
        font-size: 1rem;
    }

    form .btn {
        background-color: #25D366;
        color: white;
        border: none;
        padding: 12px 20px;
        font-weight: bold;
        cursor: pointer;
        border-radius: 30px;
        transition: background 0.3s ease;
        max-width: 500px;
        width: 100%;
    }

    form .btn:hover {
        background-color: #1da955;
    }

    .aula-experimental {
        padding: 4rem 64px;
        text-align: center;
        /*background-color: #f7f9f9;  tom leve de fundo para destacar */
        border-radius: 12px;

        margin: -15px auto;
        max-width: 500px;
    }

    .aula-experimental .whatsapp-btn {
        display: inline-flex;
        align-items: center;
        background-color: #25D366;
        color: white;
        font-weight: 700;
        font-size: 1.3rem;
        padding: 14px 24px;
        border-radius: 30px;
        text-decoration: none;
        box-shadow: 0 6px 15px rgba(37, 211, 102, 0.5);
        transition: background-color 0.3s ease, box-shadow 0.3s ease;
        cursor: pointer;
    }

    .aula-experimental .whatsapp-btn img {
        width: 28px;
        height: 28px;
        margin-right: 12px;
    }

    .aula-experimental .whatsapp-btn:hover {
        background-color: #1ebe57;
        box-shadow: 0 8px 20px rgba(30, 190, 87, 0.7);
    }

    /* Limite a largura em telas maiores */
    @media screen and (min-width: 768px) {

        .imagem-destaque,
        .grid-galeria img {
            max-width: 700px;
        }

        .hero .whatsapp-btn {
            top: 10px;
            right: 15px;
            padding: 8px 12px;
            font-size: 0.9rem;
        }

        .hero .whatsapp-btn img {
            width: 20px;
            height: 20px;
            margin-right: 8px;
        }

        .aula-experimental {
            padding: 2rem 16px;
            max-width: 90%;
        }

        .aula-experimental .whatsapp-btn {
            font-size: 1.1rem;
            padding: 12px 18px;
        }

        .aula-experimental .whatsapp-btn img {
            width: 24px;
            height: 24px;
            margin-right: 10px;
        }

    }

    @media screen and (max-width: 767px) {

        .hero .whatsapp-btn,
        .aula-experimental .whatsapp-btn {
            font-size: 0.5rem;
            padding: 5px 10px;
        }

        .hero .whatsapp-btn img,
        .aula-experimental .whatsapp-btn img {
            width: 20px;
            height: 20px;
            margin-right: 8px;
        }
    }
</style>

<main class="publico">
    <section class="hero">
        <img src="assets/img/aula-aikido.jpg" alt="Aula de Aikido" class="hero-img">
        <a href="https://wa.me/5542991179118?text=Olá,%20gostaria%20de%20agendar%20uma%20aula%20experimental%20de%20Aikido"
            target="_blank" class="whatsapp-btn">
            <img src="assets/img/whatsapp-icon.png" alt="Agendar no WhatsApp">
            Agende uma aula
        </a>
    </section>

    <section class="sobre">
        <div class="container">
            <h3>Sobre o Dojo</h3>
            <p class="sobre-o-dojo">O <?= $nomeDojo ?> tem como missão promover o desenvolvimento pessoal através dos princípios do Aikido:
                harmonia, disciplina e autoconhecimento.</p>
            <img src="assets/img/dojo-treino.jpg" alt="Treino no dojo" class="imagem-destaque imagem-ampliavel"
                data-full="assets/img/dojo-treino.jpg">
        </div>
    </section>

    <section class="historia">
        <div class="container">
            <h3>Nossa História</h3>
            <ul class="linha-do-tempo">
                <li><strong>1942:</strong> Fundação do Aikido por Morihei Ueshiba</li>
                <li><strong>1985:</strong> Introdução do Aikido na nossa cidade</li>
                <li><strong>2012:</strong> Início do <?= $nomeDojo ?></li>
            </ul>
        </div>
    </section>

    <section class="galeria">
        <div class="container">
            <h3>Galeria</h3>
            <div class="grid-galeria">
                <img src="assets/img/foto1.jpg" alt="Treino 1" class="imagem-ampliavel"
                    data-full="assets/img/foto1.jpg">
                <img src="assets/img/foto2.jpg" alt="Treino 2" class="imagem-ampliavel"
                    data-full="assets/img/foto2.jpg">
                <img src="assets/img/foto3.jpg" alt="Treino 3" class="imagem-ampliavel"
                    data-full="assets/img/foto3.jpg">
            </div>
        </div>
    </section>

    <section class="aula-experimental" id="aula-experimental" style="padding: 1rem 64px 1rem; text-align: center;">
        <a href="https://wa.me/5542991179118?text=Olá,%20gostaria%20de%20agendar%20uma%20aula%20experimental%20de%20Aikido"
            target="_blank" class="whatsapp-btn"
            style="font-size: 1.2rem; max-width: 300px; margin: 0 auto; display: inline-flex; justify-content: center;">
            <img src="assets/img/whatsapp-icon.png" alt="Agendar no WhatsApp"
                style="width: 28px; height: 28px; margin-right: 12px;">
            Agende uma aula
        </a>
    </section>


    <!-- Lightbox -->
    <div class="lightbox" id="lightbox">
        <span class="close">&times;</span>
        <img class="lightbox-content" id="lightbox-img" alt="Imagem ampliada">
    </div>

</main>

<script>
    document.querySelectorAll('.imagem-ampliavel').forEach(img => {
        img.addEventListener('click', function () {
            const lightbox = document.getElementById('lightbox');
            const lightboxImg = document.getElementById('lightbox-img');
            lightboxImg.src = this.dataset.full;
            lightbox.style.display = 'flex';
        });
    });

    document.querySelector('.lightbox .close').addEventListener('click', () => {
        document.getElementById('lightbox').style.display = 'none';
    });

    document.getElementById('lightbox').addEventListener('click', function (e) {
        if (e.target === this) {
            this.style.display = 'none';
        }
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>