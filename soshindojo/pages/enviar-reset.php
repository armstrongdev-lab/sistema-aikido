<?php
include '../includes/db.php';
require '../vendor/autoload.php'; // autoload do Composer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];

    // Verificar se o e-mail está cadastrado
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    if ($usuario) {
        $token = bin2hex(random_bytes(32));
        $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Salvar token e validade
        $stmt = $pdo->prepare("UPDATE usuarios SET reset_token = ?, reset_expira = ? WHERE id = ?");
        $stmt->execute([$token, $expira, $usuario['id']]);

        // Configurar e enviar e-mail via PHPMailer
        $link = "https://soshindojo.online/pages/resetar-senha.php?token=$token";

        $mail = new PHPMailer(true);
        $mail->CharSet = 'UTF-8'; // ← ESSA LINHA ADICIONA UTF-8
        try {
            $mail->isSMTP();
            $mail->Host = 'smtps.uhserver.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'admin@soshindojo.online'; // e-mail remetente
            $mail->Password = 'Arms@060788'; // senha do e-mail
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('admin@soshindojo.online', 'Sistema Aikido');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Redefinição de senha - Aikido';

            $mail->Body = "
            <html>
            <head>
              <style>
                .container {
                  font-family: Arial, sans-serif;
                  color: #333;
                  background-color: #f7f7f7;
                  padding: 30px;
                  border-radius: 10px;
                  max-width: 600px;
                  margin: auto;
                }
                .button {
                  background-color: #007BFF;
                  color: white;
                  padding: 12px 20px;
                  text-decoration: none;
                  border-radius: 5px;
                  display: inline-block;
                  margin-top: 15px;
                }
                .small {
                  font-size: 12px;
                  color: #777;
                  margin-top: 20px;
                }
                .logo {
                  text-align: center;
                  margin-bottom: 20px;
                }
              </style>
            </head>
            <body>
              <div class='container'>
                <div class='logo'>
                  <img src='https://soshindojo.online/assets/img/logo.png' alt='Soshin Dojo' width='120'>
                </div>
                <h2>Redefinição de Senha</h2>
                <p>Olá!</p>
                <p>Recebemos uma solicitação para redefinir sua senha no <strong>Sistema Aikido</strong>.</p>
                <p>Para continuar com a redefinição, clique no botão abaixo:</p>
                <a href='$link' class='button'>Redefinir Senha</a>
                <p class='small'>Este link é válido por 1 hora. Caso você não tenha solicitado a redefinição, pode ignorar este e-mail com segurança.</p>
                <p>Atenciosamente,<br>Equipe Soshin Dojo</p>
              </div>
            </body>
            </html>
            ";

            // Texto alternativo (para clientes que não suportam HTML)
            $mail->AltBody = "Olá!\n\nRecebemos uma solicitação para redefinir sua senha no Sistema Aikido.\n\nAcesse o link abaixo para continuar:\n$link\n\nEste link é válido por 1 hora.\n\nAtenciosamente,\nEquipe Soshin Dojo";

            $mail->send();
            echo "<script>alert('E-mail enviado! Verifique sua caixa de entrada.'); window.location.href = 'login.php';</script>";
        } catch (Exception $e) {
            echo "<script>alert('Erro ao enviar e-mail: {$mail->ErrorInfo}'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('E-mail não encontrado.'); window.history.back();</script>";
    }
}
?>
