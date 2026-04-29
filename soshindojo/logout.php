<?php
session_start();
session_unset(); // limpa todas as variáveis de sessão
session_destroy(); // destrói a sessão

header('Location: index.php');
 // ou o caminho correto da sua tela de login
exit;
?>
