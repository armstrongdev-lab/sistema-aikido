<?php
require_once('../includes/header.php');
?>

<main style="max-width: 100%; /*margin: 30px auto;*/ padding: 20px;">

    <h1 style="text-align:center; margin-bottom: 20px;">Localização do Dojo</h1>

    <div style="width: 100%; height: 450px; box-shadow: 0 3px 10px rgba(0,0,0,0.2); border-radius: 8px; overflow: hidden;">
        <iframe
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d447.38559775601783!2d-51.09404448970263!3d-26.226434623269654!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x94e661fbbc3c3fbd%3A0x7830cf999a967f28!2sAcademia%20%C3%94mega!5e0!3m2!1spt-BR!2sbr!4v1750884669236!5m2!1spt-BR!2sbr"
            width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade">
        </iframe>
    </div>

    <div style="text-align: center; margin-top: 20px;">
        <a href="https://www.google.com/maps/search/?api=1&query=Academia+Ômega,+União+da+Vitória,+PR" 
           target="_blank" 
           style="display: inline-block; background-color: #000000; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; transition: background-color 0.3s;">
            📍 Abrir no Google Maps
        </a>
    </div>

</main>

<?php
require_once('../includes/footer.php');
?>
