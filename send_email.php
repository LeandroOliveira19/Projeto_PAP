<?php
// filepath: c:\xampp\htdocs\Projeto_PAP\send_email.php

// Inclui a conexão com o banco de dados
include 'db.php';

// Load Composer's autoloader
require 'vendor/autoload.php';

// Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Função para enviar email de confirmação ou recuperação
function sendConfirmationEmail($to, $name, $token, $type, $temp_password = null)
{
    $subject = '';
    $message = '';
    $altBody = ''; // Adicionado para texto plano

    // Configurações do email com base no tipo
    if ($type == "register") {
        $subject = "Confirmação de Cadastro - MyWellness";
        $confirm_link = 'http://mywellness.duckdns.org/Projeto_PAP/confirm.php?token=' . $token; // Use a variável correta
        $message = "
        <html>
        <head>
            <title>Confirmação de Cadastro</title>
        </head>
        <body>
            <h2>Olá, " . htmlspecialchars($name) . "!</h2>
            <p>Obrigado por se cadastrar na MyWellness. Por favor, clique no link abaixo para confirmar seu cadastro:</p>
            <p><a href='" . $confirm_link . "'>" . $confirm_link . "</a></p>
            <p>Se você não se cadastrou, ignore este email.</p>
            <br>
            <p>Obrigado,<br>Equipa MyWellness</p>
        </body>
        </html>
        ";
        $altBody = "Olá, " . htmlspecialchars($name) . "!\n\n";
        $altBody .= "Obrigado por se cadastrar na MyWellness. Por favor, copie e cole o seguinte link no seu navegador para confirmar seu cadastro:\n";
        $altBody .= $confirm_link . "\n\n";
        $altBody .= "Se você não se cadastrou, ignore este email.\n\n";
        $altBody .= "Obrigado,\nEquipa MyWellness";

    } else if ($type == "reset") {
        $subject = "Recuperação de Senha - MyWellness";
        // ***** CORREÇÃO APLICADA AQUI *****
        $reset_link = 'http://mywellness.duckdns.org/Projeto_PAP/reset_password.php?token=' . $token;
        // ***********************************
        $message = "
        <html>
        <head>
            <title>Recuperação de Senha</title>
        </head>
        <body>
            <h2>Olá, " . htmlspecialchars($name) . "!</h2>
            <p>Recebemos um pedido para redefinir a sua senha na plataforma MyWellness.</p>
            <p>Clique no link abaixo para definir uma nova senha:</p>
            <p><a href='" . $reset_link . "'>" . $reset_link . "</a></p>
            <p>Este link expirará em 1 hora.</p>
            <p>Se não solicitou esta alteração, pode ignorar este e-mail.</p>
            <br>
            <p>Obrigado,<br>Equipa MyWellness</p>
        </body>
        </html>
        ";
        $altBody = "Olá, " . htmlspecialchars($name) . "!\n\n";
        $altBody .= "Recebemos um pedido para redefinir a sua senha na plataforma MyWellness.\n";
        $altBody .= "Copie e cole o seguinte link no seu navegador para definir uma nova senha:\n";
        $altBody .= $reset_link . "\n\n";
        $altBody .= "Este link expirará em 1 hora.\n\n";
        $altBody .= "Se não solicitou esta alteração, pode ignorar este e-mail.\n\n";
        $altBody .= "Obrigado,\nEquipa MyWellness";

    } else if ($type == "register_admin") {
        $subject = "Conta Criada - Defina sua Senha - MyWellness";
        // Links para localhost e servidor (adapte conforme necessário)
        $confirm_link_localhost = 'http://mywellness.duckdns.org/Projeto_PAP/confirm.php?token=' . $token;

        $message = "
        <html>
        <head>
            <title>Conta Criada - Defina sua Senha</title>
        </head>
        <body>
            <h2>Olá, " . htmlspecialchars($name) . "!</h2>
            <p>Uma conta foi criada para você na plataforma MyWellness.</p>
            <p>Sua senha temporária é: <strong>" . htmlspecialchars($temp_password) . "</strong></p>
            <p>Por favor, clique no link correspondente ao seu ambiente para confirmar seu cadastro. Após a confirmação, faça login com a senha temporária e será solicitado que defina uma nova senha:</p>
            <p><strong>Link:</strong> <a href='" . $confirm_link_localhost . "'>" . $confirm_link_localhost . "</a></p>
            <p>Se você não esperava esta conta, entre em contato com o administrador.</p>
            <br>
            <p>Obrigado,<br>Equipa MyWellness</p>
        </body>
        </html>
        ";
        $altBody = "Olá, " . htmlspecialchars($name) . "!\n\n";
        $altBody .= "Uma conta foi criada para você na plataforma MyWellness.\n";
        $altBody .= "Sua senha temporária é: " . htmlspecialchars($temp_password) . "\n\n";
        $altBody .= "Por favor, use o link correspondente ao seu ambiente para confirmar seu cadastro:\n\n";
        $altBody .= "Link: " . $confirm_link_localhost . "\n\n";
        $altBody .= "Após a confirmação, faça login com a senha temporária e será solicitado que defina uma nova senha.\n\n";
        $altBody .= "Após a confirmação, faça login com a senha temporária e será solicitado que defina uma nova senha.\n\n";
        $altBody .= "Se você não esperava esta conta, entre em contato com o administrador.\n\n";
        $altBody .= "Obrigado,\nEquipa MyWellness";
    } else {
        // Tipo inválido, não envia email
        error_log("Tentativa de envio de email com tipo inválido: " . $type);
        return false;
    }


    // Instantiation and passing `true` enables exceptions
    $mail = new PHPMailer(true);

    try {
        //Server settings
        $mail->SMTPDebug = SMTP::DEBUG_OFF; // Mude para SMTP::DEBUG_SERVER para depuração detalhada
        $mail->isSMTP(); // Send using SMTP
        $mail->Host       = 'smtp.gmail.com'; // Set the SMTP server to send through
        $mail->SMTPAuth   = true; // Enable SMTP authentication
        $mail->Username   = 'l2androliveir3@gmail.com'; // SMTP username (seu Gmail)
        $mail->Password   = 'lovf lhxz girt nwrk'; // SMTP password (sua Senha de App)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption
        $mail->Port       = 587; // TCP port to connect to
        $mail->CharSet    = 'UTF-8'; // Definir CharSet para UTF-8 para suportar acentos

        //Recipients
        $mail->setFrom('l2androliveir3@gmail.com', 'MyWellness'); // Seu email e nome do remetente
        $mail->addAddress($to, $name); // Add a recipient

        // Content
        $mail->isHTML(true); // Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body    = $message;
        $mail->AltBody = $altBody; // Corpo alternativo em texto plano

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log do erro em vez de apenas 'echo'
        error_log("Erro ao enviar email para {$to}. Mailer Error: {$mail->ErrorInfo}");
        // Opcional: Mostrar uma mensagem genérica ou apenas retornar false
        // echo "Ocorreu um erro ao enviar a mensagem. Tente novamente mais tarde.";
        return false;
    }
}
?>
