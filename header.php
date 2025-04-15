<?php
// A sessão geralmente é iniciada nos arquivos que incluem este header (ex: dashboard.php, trainer.php, etc.)
// Se não for o caso, descomente a linha abaixo:
// session_start();
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Título dinâmico pode ser adicionado aqui se necessário -->
    <title>MyWellness</title>

    <!-- Google Fonts (Poppins) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- FullCalendar CSS (v6) - Incluir se usado globalmente, senão apenas nas páginas necessárias -->
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/main.min.css' rel='stylesheet' />

    <!-- Estilos Principais e Tema (Variáveis CSS) -->
    <link rel="stylesheet" href="assets/css/dashboard_cliente.css">

    <!-- Estilos Específicos da Personalização do Chat -->
    <link rel="stylesheet" href="assets/css/personalizacao_chat.css">

    <!-- Favicon (Opcional) -->
    <!-- <link rel="icon" href="assets/img/favicon.ico" type="image/x-icon"> -->

</head>

<body class="dashboard-body"> <!-- Classe importante para aplicar estilos base e tema -->

    <!-- ================================================== -->
    <!-- INLINE SCRIPT para aplicar tema/cores imediatamente -->
    <!-- (Evita o "flicker" ao carregar a página)         -->
    <!-- ================================================== -->
    <script>
        (function() {
            // Aplica tema Dark/Light guardado no localStorage
            const theme = localStorage.getItem('theme');
            if (theme === 'dark') {
                document.body.classList.add('dark-mode');
            } else {
                 // Garante que a classe não esteja presente se o tema for 'light' ou não definido
                 document.body.classList.remove('dark-mode');
            }

            // Aplica cores do chat guardadas no localStorage
             const savedColors = JSON.parse(localStorage.getItem('chatColors') || '{}');
             const root = document.documentElement; // Seleciona o elemento raiz (<html>)

             // Define as propriedades CSS customizadas (variáveis) se existirem no localStorage
             if(savedColors.sentBg) root.style.setProperty('--chat-sent-bg', savedColors.sentBg);
             if(savedColors.sentText) root.style.setProperty('--chat-sent-text', savedColors.sentText);
             if(savedColors.receivedBg) root.style.setProperty('--chat-received-bg', savedColors.receivedBg);
             if(savedColors.receivedText) root.style.setProperty('--chat-received-text', savedColors.receivedText);
             // Adicionar timestamps se também forem personalizáveis no futuro
             // if(savedColors.timestampSent) root.style.setProperty('--chat-timestamp-sent', savedColors.timestampSent);
             // if(savedColors.timestampReceived) root.style.setProperty('--chat-timestamp-received', savedColors.timestampReceived);
        })();
    </script>
    <!-- ================================================== -->

    <!-- O conteúdo principal da página (incluindo sidebar e main-content)
         será inserido aqui pelos arquivos que incluem este header
         (ex: dashboard.php, trainer.php, chat.php, etc.) -->
