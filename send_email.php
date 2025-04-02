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

// Função para enviar email de confirmação
function sendConfirmationEmail($to, $name, $token, $type, $temp_password = null)
{
    // Configurações do email
    if ($type == "register") {
        $subject = "Confirmação de Cadastro";
        $message = "
        <html>
        <head>
            <title>Confirmação de Cadastro</title>
        </head>
        <body>
            <h2>Olá, $name!</h2>
            <p>Obrigado por se cadastrar. Por favor, clique no link abaixo para confirmar seu cadastro e definir sua senha:</p>
            <a href='http://localhost/Projeto_PAP/confirm.php?token=$token'>Confirmar Cadastro</a>
            <p>Se você não se cadastrou, ignore este email.</p>
        </body>
        </html>
        ";
    } else if ($type == "reset") {
        $subject = "Redefinição de Senha";
        $message = "
        <html>
        <head>
            <title>Redefinição de Senha</title>
        </head>
        <body>
            <h2>Olá, $name!</h2>
            <p>Você solicitou a redefinição da sua senha. Por favor, clique no link abaixo para definir uma nova senha:</p>
            <a href='http://localhost/Projeto_PAP/change_password.php?token=$token'>Redefinir Senha</a>
            <p>Se você não solicitou a redefinição da senha, ignore este email.</p>
        </body>
        </html>
        ";
    } else if ($type == "register_admin") {
        $subject = "Conta Criada - Defina sua Senha";
        $message = "
        <html>
        <head>
            <title>Conta Criada - Defina sua Senha</title>
        </head>
        <body>
            <h2>Olá!</h2>
            <p>Uma conta foi criada para você em nosso sistema. Sua senha temporária é: <strong>$temp_password</strong></p>
            <p>Por favor, clique no link abaixo para confirmar seu cadastro e definir sua senha:</p>
            <a href='http://localhost/Projeto_PAP/confirm.php?token=$token'>Confirmar Cadastro</a>
            <p>Após confirmar seu cadastro, você será redirecionado para fazer login e alterar sua senha.</p>
        </body>
        </html>
        ";
    }

    // Instantiation and passing `true` enables exceptions
    $mail = new PHPMailer(true);

    try {
        //Server settings
        $mail->SMTPDebug = SMTP::DEBUG_OFF; // Enable verbose debug output (change to DEBUG_SERVER for more info)
        $mail->isSMTP(); // Send using SMTP
        $mail->Host       = 'smtp.gmail.com'; // Set the SMTP server to send through
        $mail->SMTPAuth   = true; // Enable SMTP authentication
        $mail->Username   = 'l2androliveir3@gmail.com'; // SMTP username (your Gmail address)
        $mail->Password   = 'lovf lhxz girt nwrk'; // SMTP password (your App Password)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
        $mail->Port       = 587; // TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above

        //Recipients
        $mail->setFrom('l2androliveir3@gmail.com', 'Leandro'); // Your email and name
        $mail->addAddress($to, $name); // Add a recipient

        // Content
        $mail->isHTML(true); // Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body    = $message;

        $mail->send();
        return true;
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        return false;
    }
}
?>
