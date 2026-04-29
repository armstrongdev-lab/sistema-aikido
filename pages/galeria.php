<?php require_once('../includes/header.php'); ?>

<!-- Swiper.js -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css" />
<script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js"></script>

<style>

    /* Corrige o cálculo de largura do container */
.galeria-container {
    width: 100%;
    max-width: 1200px;
    margin: 50px auto;
    padding: 0 20px;
    box-sizing: border-box;
}

/* Evita scroll horizontal geral */
html, body {
    overflow-x: hidden;
    margin: 0;
    padding: 0;
}

    .galeria-titulo {
        font-size: 2rem;
        text-align: center;
        margin-bottom: 30px;
        color: #222;
        font-weight: bold;
    }

    .swiper {
        padding-bottom: 40px;
        position: relative;
    }

    .swiper-slide {
        background: #fff;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        cursor: pointer;
    }

    .swiper-slide img {
        width: 100%;
        height: 250px;
        object-fit: cover;
        display: block;
    }

    @media (max-width: 768px) {
        .swiper-slide img {
            height: 180px;
        }
    }

    @media (max-width: 600px) {
        .modal-btn {
            font-size: 1.5rem;
            width: 36px;
            height: 36px;
            background-color: rgba(0, 0, 0, 0.6);
            border-radius: 50%;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            border: none;
        }

        .modal-prev {
            left: 8px;
        }

        .modal-next {
            right: 8px;
        }

        .modal-close {
            top: 12px;
            right: 15px;
            font-size: 1.4rem;
        }
    }

    /* Botões de navegação exclusivos para fotos */
    .fotos-next,
    .fotos-prev {
        color: #fff;
        background-color: rgba(255, 255, 255, 0);
        width: 40px;
        height: 40px;
        border-radius: 50%;
        top: 50%;
        transform: translateY(-50%);
        position: absolute;
        z-index: 10;
        cursor: pointer;
    }

    .fotos-next {
        right: 10px;
    }

    .fotos-prev {
        left: 10px;
    }

    /* Botões de navegação exclusivos para vídeos */
    .videos-next,
    .videos-prev {
        color: #fff;
        background-color: rgba(255, 255, 255, 0);
        width: 40px;
        height: 40px;
        border-radius: 50%;
        top: 50%;
        transform: translateY(-50%);
        position: absolute;
        z-index: 10;
        cursor: pointer;
    }

    .videos-next {
        right: 10px;
    }

    .videos-prev {
        left: 10px;
    }

    /* Modal Slideshow */
    #modalGaleria {
        display: none;
        position: fixed;
        z-index: 9999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.95);
        justify-content: center;
        align-items: center;
    }

    #modalGaleria img {
        max-width: 90%;
        max-height: 80vh;
        border-radius: 8px;
    }

    .modal-btn {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        font-size: 3rem;
        color: white;
        background: none;
        border: none;
        cursor: pointer;
        z-index: 10001;
    }

    .modal-prev {
        left: 30px;
    }

    .modal-next {
        right: 30px;
    }

    .modal-close {
        position: absolute;
        top: 30px;
        right: 40px;
        font-size: 2rem;
        color: white;
        cursor: pointer;
    }

    /* Estilo para vídeos */
    .video-container {
        position: relative;
        padding-bottom: 56.25%;
        /* 16:9 */
        height: 0;
        overflow: hidden;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .video-container iframe,
    .video-container video {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
    }
</style>

<!-- Galeria de Fotos -->
<main class="galeria-container">
    <h1 class="galeria-titulo">Fotos</h1>
    <div class="swiper galeria-swiper">
        <div class="swiper-wrapper">
            <?php
            $imagens = ["foto1.jpg", "foto2.jpg", "foto3.jpg", "foto4.jpg", "foto5.jpg"];
            foreach ($imagens as $index => $img) {
                echo "<div class='swiper-slide' onclick='abrirModal($index)'>
                        <img src='../assets/galeria/fotos/$img' alt='Foto $index'>
                      </div>";
            }
            ?>
        </div>
        <div class="swiper-button-next fotos-next"></div>
        <div class="swiper-button-prev fotos-prev"></div>
        <div class="swiper-pagination fotos-pagination"></div>
    </div>
</main>

<!-- Modal -->
<div id="modalGaleria">
    <span class="modal-close" onclick="fecharModal()">&#10005;</span>
    <button class="modal-btn modal-prev" onclick="trocarImagem(-1)">&#10094;</button>
    <img id="modalImagem" src="" alt="Imagem ampliada">
    <button class="modal-btn modal-next" onclick="trocarImagem(1)">&#10095;</button>
</div>

<!-- Galeria de Vídeos (YouTube) -->
<main class="galeria-container">
    <h1 class="galeria-titulo">Vídeos</h1>
    <div class="swiper video-swiper">
        <div class="swiper-wrapper">
            <?php
            $videos_youtube = [
                "https://www.youtube.com/embed/0afWMLpO5oE",
                "https://www.youtube.com/embed/zvHtX_mwacE",
                "https://www.youtube.com/embed/GP1HMeniRpk"
            ];
            foreach ($videos_youtube as $url) {
                echo "
                <div class='swiper-slide'>
                    <div class='video-container'>
                        <iframe src='$url' frameborder='0' allow='accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture' allowfullscreen></iframe>
                    </div>
                </div>";
            }
            ?>
        </div>
        <div class="swiper-button-next videos-next"></div>
        <div class="swiper-button-prev videos-prev"></div>
        <div class="swiper-pagination videos-pagination"></div>
    </div>
</main>

<!-- Scripts -->
<script>
    const imagens = [
        "../assets/galeria/fotos/foto1.jpg",
        "../assets/galeria/fotos/foto2.jpg",
        "../assets/galeria/fotos/foto3.jpg",
        "../assets/galeria/fotos/foto4.jpg",
        "../assets/galeria/fotos/foto5.jpg"
    ];
    let indiceAtual = 0;

    function abrirModal(indice) {
        indiceAtual = indice;
        document.getElementById('modalImagem').src = imagens[indiceAtual];
        document.getElementById('modalGaleria').style.display = 'flex';
    }

    function fecharModal() {
        document.getElementById('modalGaleria').style.display = 'none';
    }

    function trocarImagem(direcao) {
        indiceAtual += direcao;
        if (indiceAtual < 0) indiceAtual = imagens.length - 1;
        if (indiceAtual >= imagens.length) indiceAtual = 0;
        document.getElementById('modalImagem').src = imagens[indiceAtual];
    }

    // Swiper para fotos
    new Swiper('.galeria-swiper', {
        slidesPerView: 1,
        spaceBetween: 20,
        loop: true,
        autoplay: {
            delay: 4000,
            disableOnInteraction: false,
        },
        pagination: {
            el: '.fotos-pagination',
            clickable: true,
        },
        navigation: {
            nextEl: '.fotos-next',
            prevEl: '.fotos-prev',
        },
        breakpoints: {
            640: { slidesPerView: 1 },
            768: { slidesPerView: 2 },
            1024: { slidesPerView: 3 },
        }
    });

    // Swiper para vídeos
    new Swiper('.video-swiper', {
        slidesPerView: 1,
        spaceBetween: 20,
        loop: true,
        autoplay: {
            delay: 5000,
            disableOnInteraction: false,
        },
        pagination: {
            el: '.videos-pagination',
            clickable: true,
        },
        navigation: {
            nextEl: '.videos-next',
            prevEl: '.videos-prev',
        },
        breakpoints: {
            640: { slidesPerView: 1 },
            1024: { slidesPerView: 2 },
        }
    });
</script>

<?php require_once('../includes/footer.php'); ?>
