<?php
require_once __DIR__ . '/../includes/header.php'; // reutiliza seu header dinâmico
?>
<style>

    
.sobre-container {
        max-width: 1000px;
        margin: 0 auto;
        padding: 40px 20px;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        line-height: 1.8;
        color: #333;
}

.sobre-container h2 {
        font-size: 2.2rem;
        color: #1a1a1a;
        border-left: 6px solid #a00;
        padding-left: 15px;
        margin-bottom: 25px;
}

.sobre-container p {
        font-size: 1.1rem;
        margin-bottom: 18px;
        text-align: justify;
}

    .divider {
        margin: 60px 0;
        border: none;
        border-top: 2px dashed #ccc;
        width: 80%;
    }

.sobre-section {
    margin-bottom: 40px;
}

.equipe {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
}

.equipe-membro {
    flex: 1 1 200px;
    text-align: center;
}

.equipe-membro p {
    text-align: center;
}


.equipe-membro img {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    margin-bottom: 10px;
}

@media screen and (max-width: 600px) {
    .sobre-container {
        padding: 15px;
    }

    .sobre-container h2 {
        font-size: 1.5rem;
    }

    .sobre-container p {
        font-size: 1rem;
    }
}

/* Botão WhatsApp flutuante */
.whatsapp-fixo {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background-color: #25D366;
    color: white;
    border-radius: 50%;
    padding: 15px;
    font-size: 24px;
    z-index: 999;
    text-decoration: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.3);
}

.whatsapp-fixo:hover {
    background-color: #1ebe5d;
}
</style>

<div class="sobre-container">
    <div class="sobre-section">
        <h2>Sobre o Dojo</h2>
        <p>Fundado com a missão de promover a disciplina, respeito e bem-estar físico e mental, nosso Dojo é um espaço de aprendizado contínuo e prática do Aikido. Aqui, cada aluno trilha seu próprio caminho com apoio e orientação personalizada.</p>
    </div>

    <hr class="divider">

    <div class="sobre-section">
        <h2>Nossa Filosofia</h2>
        <p>Seguimos os princípios do Aikido tradicional: harmonia, paz e autocontrole. Acreditamos que o verdadeiro combate é interno e que, por meio da prática constante, desenvolvemos caráter, equilíbrio e compaixão.</p>
    </div>

    <hr class="divider">

    <div class="sobre-section">
        <h2>Missão, Visão e Valores</h2>
        <p><strong>Missão:</strong> Proporcionar um ambiente seguro e respeitoso onde os praticantes possam crescer fisicamente, mentalmente e espiritualmente por meio do Aikido.</p>
        <p><strong>Visão:</strong> Ser referência regional na formação de aikidoístas comprometidos com os princípios da arte e com o desenvolvimento humano.</p>
        <p><strong>Valores:</strong> Respeito, disciplina, humildade, perseverança e harmonia.</p>
    </div>

    <hr class="divider">
    
    <div class="sobre-section">
        <h2>Nossa Equipe</h2>
        <div class="equipe">
            <div class="equipe-membro">
                <img src="../assets/img/sensei-marlon-angelo-maltauro.jpg" alt="Sensei Marlon Angelo Maltauro">
                <p><strong>Sensei Marlon Angelo Maltauro</strong><br>5º Dan</p>
            </div>
            <div class="equipe-membro">
                <img src="../assets/img/Wagner-de-Moura-Santos.jpg" alt="Waka">
                <p><strong>Sensei Wagner de Moura Santos</strong><br>3º Dan</p>
            </div>
            <div class="equipe-membro">
                <img src="../assets/img/Marcelo-Gabardo.jpg" alt="Marcelo">
                <p><strong>Marcelo R. Gabardo</strong><br>2º Dan</p>
            </div>
            <!-- Adicione mais membros se quiser -->
        </div>
    </div>
</div>
<?php include('../includes/footer.php'); ?>
