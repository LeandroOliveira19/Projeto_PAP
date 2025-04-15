    <!-- O conteúdo principal da página (geralmente dentro de <main class="main-content">)
         termina antes deste ponto, nos arquivos que incluem o footer -->

    <!-- ================================================== -->
    <!-- Scripts JS incluídos no final do body -->
    <!-- (Melhor performance de carregamento)             -->
    <!-- ================================================== -->

    <!-- jQuery (Se algum plugin ou código seu ainda depender dele) -->
    <!-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> -->

    <!-- Bootstrap Bundle JS (inclui Popper.js) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- FullCalendar JS (v6) - Incluir se usado globalmente -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/main.min.js'></script>
    <!-- Locales do FullCalendar (opcional, exemplo: Português) -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/locales/pt.js'></script>

    <!-- Script de Tema (Dark/Light) e Cores do Chat -->
    <script src="assets/js/theme.js"></script>

    <!-- Outros scripts específicos da página podem ser incluídos aqui
         pelos arquivos que incluem este footer, ou podem vir antes de theme.js
         se theme.js depender deles (o que não é o caso aqui) -->

    <!-- Exemplo de como uma página específica poderia adicionar seu script: -->
    <?php /*
    if (isset($page_scripts) && is_array($page_scripts)) {
        foreach ($page_scripts as $script) {
            echo '<script src="' . htmlspecialchars($script) . '"></script>';
        }
    }
    */ ?>

</body>
</html>
