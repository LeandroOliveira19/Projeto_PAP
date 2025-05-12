<?php
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Inclui a conexão com o banco de dados
include 'db.php';

// Inclui o cabeçalho da página
include 'header.php';


// Pega o nome do usuário da sessão
$client_name = $_SESSION['name'] ?? 'Utilizador';

// Define qual link da sidebar deve estar ativo
$active_page = basename($_SERVER['PHP_SELF']);

// --- Preparar dados das aulas ---
$all_classes_json = '[]'; // Will store all class data for JS
$classes_data = [];

try {
    // Busca todas as aulas disponíveis com contagem de participantes, status de inscrição e se é favorita
    $stmt = $pdo->prepare("
        SELECT
            c.id,
            c.name,
            c.description,
            c.schedule,
            c.duration,
            c.max_participants,
            c.trainer_id,
            u.name AS trainer_name,
            u.profile_picture AS trainer_avatar,
            (SELECT COUNT(*) FROM class_participants cp WHERE cp.class_id = c.id) AS current_participants,
            EXISTS(SELECT 1 FROM class_participants cp WHERE cp.class_id = c.id AND cp.user_id = :user_id) AS is_enrolled,
            EXISTS(SELECT 1 FROM user_favorite_classes ufc WHERE ufc.class_id = c.id AND ufc.user_id = :user_id) AS is_favorite
        FROM classes c
        JOIN users u ON c.trainer_id = u.id
        ORDER BY c.schedule
    ");
    $stmt->execute(['user_id' => $_SESSION['user_id']]);
    $classes = $stmt->fetchAll();

    if ($classes) {
        $temp_classes_data = [];
        foreach ($classes as $class_item) {
            $vagas_disponiveis = $class_item['max_participants'] - $class_item['current_participants'];
            $data_aula_ts = strtotime($class_item['schedule']);
            $aula_passou = $data_aula_ts < time();

            // Fetch participants for this class
            $stmt_participants = $pdo->prepare("
                SELECT u.id, u.name, u.profile_picture
                FROM class_participants cp
                JOIN users u ON cp.user_id = u.id
                WHERE cp.class_id = ?
                ORDER BY u.name
            ");
            $stmt_participants->execute([$class_item['id']]);
            $participants = $stmt_participants->fetchAll(PDO::FETCH_ASSOC);

            // Store class data for the new layout
            $temp_classes_data[] = [
                'id' => $class_item['id'],
                'name' => $class_item['name'],
                'schedule' => $class_item['schedule'],
                'trainer_id' => $class_item['trainer_id'],
                'description' => $class_item['description'],
                'trainer_name' => $class_item['trainer_name'],
                'trainer_avatar' => $class_item['trainer_avatar'],
                'duration' => $class_item['duration'],
                'max_participants' => $class_item['max_participants'],
                'current_participants' => $class_item['current_participants'],
                'vagas_disponiveis' => $vagas_disponiveis,
                'aula_passou' => $aula_passou,
                'is_enrolled' => (bool)$class_item['is_enrolled'],
                'is_favorite' => (bool)$class_item['is_favorite'], // Adicionado
                'participants' => $participants
            ];
        }
        // Converte o array PHP para JSON para uso no JavaScript
        $all_classes_json = json_encode($temp_classes_data);
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar aulas: " . $e->getMessage());
    echo "<div class='alert alert-danger'>Erro ao carregar dados das aulas.</div>";
}

?>

<?php include 'sidebar_client.php'; ?>

<style>
    .classes-header {
        display: flex;
        align-items: center;
        padding: 10px 15px;
        background-color: var(--bs-body-bg); /* Adapts to theme */
        border-bottom: 1px solid var(--bs-border-color);
        position: sticky;
        top: 0;
        z-index: 1000;
    }
    .classes-header .back-button {
        font-size: 1.2rem;
        color: var(--bs-body-color);
        text-decoration: none;
        margin-right: 15px;
    }
    .classes-header .page-title {
        font-size: 1.25rem; /* Medium to large */
        font-weight: 500; /* Medium weight */
        color: var(--bs-body-color);
        margin: 0 auto; /* Center title */
        transform: translateX(-15px); /* Adjust for back button space */
    }

    .week-selector-container {
        padding: 10px 0;
        background-color: var(--bs-body-bg);
        text-align: center;
    }
    .month-year-display {
        font-size: 0.9rem;
        font-weight: 500;
        margin-bottom: 8px;
        color: var(--bs-secondary-color);
    }
    .week-navigation {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 10px;
    }
    .week-navigation button {
        background: none;
        border: none;
        font-size: 1.2rem;
        color: var(--bs-primary);
        cursor: pointer;
        padding: 5px 10px;
    }
    .days-strip {
        display: flex;
        overflow-x: auto; /* Allows horizontal scroll if needed, though 7 days should fit */
        justify-content: center; /* Center days if they don't fill width */
        flex-grow: 1;
        padding: 5px 0;
    }
    .day-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 8px 5px;
        min-width: 45px; /* Ensure days are not too squeezed */
        cursor: pointer;
        border-radius: 50%; /* For circular selection */
        transition: background-color 0.3s, color 0.3s;
        margin: 0 2px;
    }
    .day-item .day-abbr {
        font-size: 0.7rem;
        font-weight: 500;
        text-transform: uppercase;
        color: var(--bs-secondary-color);
    }
    .day-item .day-number {
        font-size: 1rem;
        font-weight: 500;
        color: var(--bs-body-color);
    }
    .day-item.selected {
        background-color: #FFA500; /* Orange */
    }
    .day-item.selected .day-abbr,
    .day-item.selected .day-number {
        color: #FFFFFF; /* White text */
    }
    .day-item:not(.selected):hover {
        background-color: var(--bs-tertiary-bg);
    }

    .search-filter-bar {
        display: flex;
        align-items: center;
        padding: 10px 15px;
        background-color: var(--bs-body-bg);
        border-bottom: 1px solid var(--bs-border-color);
    }
    .search-input-group {
        display: flex;
        align-items: center;
        background-color: var(--bs-tertiary-bg);
        border-radius: 20px;
        padding: 5px 10px;
        flex-grow: 1;
    }
    .search-input-group i {
        color: var(--bs-secondary-color);
        margin-right: 8px;
    }
    .search-input-group input {
        border: none;
        background: transparent;
        outline: none;
        width: 100%;
        color: var(--bs-body-color);
    }
    .search-input-group input::placeholder {
        color: var(--bs-secondary-color);
    }
    .filter-icons button {
        background: none;
        border: none;
        font-size: 1.2rem;
        color: var(--bs-secondary-color);
        margin-left: 10px;
        cursor: pointer;
    }
    .filter-icons button.active { /* Estilo para botão de filtro ativo */
        color: var(--bs-primary); /* Ou outra cor de destaque */
    }


    #daily-classes-container {
        padding: 15px;
    }
    .class-card {
        display: flex;
        background-color: var(--bs-body-bg);
        border: 1px solid #000000; /* Alterado para contorno preto */
        border-radius: 8px;
        margin-bottom: 15px;
        overflow: hidden;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        /* cursor: pointer;  Removido para permitir clique no coração */
    }
    .class-type-band {
        width: 8px;
        flex-shrink: 0;
    }
    .class-card-content {
        padding: 12px 15px;
        flex-grow: 1;
        position: relative;
    }
    .class-card-main-info { /* Novo wrapper para info clicável */
        cursor: pointer;
        margin-bottom: 5px; /* Espaço para o coração */
    }
    .class-time {
        font-size: 0.9rem;
        font-weight: 500;
        color: var(--bs-body-color);
        margin-bottom: 2px;
    }
    .class-name {
        font-size: 1.1rem;
        font-weight: bold;
        color: var(--bs-body-color);
        margin-bottom: 5px;
    }
    .instructor-bubbles {
        display: flex;
        gap: 5px;
        margin-bottom: 8px;
    }
    .instructor-bubble {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background-color: var(--bs-secondary-bg);
        color: var(--bs-secondary-color);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: 500;
    }
    .instructor-bubble img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 50%;
    }
    .spots-badge {
        position: absolute;
        top: 12px;
        right: 15px;
        background-color: #007bff; /* Blue */
        color: white;
        font-size: 0.75rem;
        padding: 3px 8px;
        border-radius: 10px;
    }
    .favorite-icon { /* Estilo para o ícone de favorito no card */
        position: absolute;
        bottom: 5px;
        right: 15px;
        font-size: 1.3rem;
        cursor: pointer;
        color: var(--bs-secondary-color);
    }
    .favorite-icon.is-favorite {
        color: #ffc107; /* Amarelo/Laranja para favorito */
    }
    .status-finished {
        font-size: 0.8rem;
        color: var(--bs-secondary-color);
        margin-top: 5px;
    }
    .cancel-enrollment-btn {
        font-size: 0.85rem; /* Um pouco menor que botões padrão */
        font-size: 0.75rem; /* Reduzido ainda mais */
        margin-top: 0.5rem; /* Adiciona um espaço acima do botão */
        padding: 0.2rem 0.4rem; /* Padding reduzido para diminuir o tamanho */
        display: block; /* Necessário para que margin auto funcione para centralizar */
        margin-left: auto; /* Centraliza o botão */
        margin-right: auto; /* Centraliza o botão */
        width: fit-content; /* Faz o botão ter a largura do seu conteúdo */
    }
    .no-classes {
        text-align: center;
        padding: 20px;
        color: var(--bs-secondary-color);
    }

    /* Estilos para o Modal de Filtros */
    .filter-modal-section {
        margin-bottom: 1rem;
    }
    .filter-modal-section h6 {
        margin-bottom: 0.5rem;
        font-weight: 500;
    }
    .filter-options-container {
        max-height: 200px;
        overflow-y: auto;
        padding: 5px;
        border: 1px solid var(--bs-border-color);
        border-radius: var(--bs-border-radius);
    }
    /* --- ALTERAÇÃO AQUI: Cor do texto dos labels no modal de filtros --- */
    .filter-options-container .form-check-label {
        color: #000; /* Define a cor do texto dos labels para preto */
    }
    /* Nota: Definir a cor diretamente para #000 (preto) pode não ser ideal em modo escuro.
       Se quiser que a cor se adapte ao tema, use:
       .dark-mode .filter-options-container .form-check-label {
           color: var(--bs-body-color); // Usa a cor de texto padrão do tema (claro no modo escuro)
       }
       Mas, seguindo o pedido, definimos para preto (#000).
    */
    /* --- ALTERAÇÃO AQUI: Cor do texto dos títulos do modal de filtros --- */
    #filterModalLabel,
    .filter-modal-section h6 {
        color: #000 !important; /* Define a cor do texto dos títulos para preto */
    }
    /* Nota: Usar !important pode ser necessário para sobrescrever estilos do Bootstrap.
       Para modo escuro, idealmente seria var(--bs-body-color) se o fundo do modal for escuro,
       ou uma cor escura específica se o fundo do modal permanecer claro.
    */
    /* --- FIM DA ALTERAÇÃO --- */
</style>

<!-- Conteúdo Principal -->
<main class="main-content">
    <div class="classes-header">
        <a href="dashboard.php" class="back-button"><i class="fas fa-arrow-left"></i></a>
        <h1 class="page-title">Horário</h1>
    </div>

    <div class="week-selector-container">
        <div id="month-year-display" class="month-year-display"></div>
        <div class="week-navigation">
            <button id="prev-week-btn"><i class="fas fa-chevron-left"></i></button>
            <div id="days-strip" class="days-strip">
                <!-- Days will be populated by JS -->
            </div>
            <button id="next-week-btn"><i class="fas fa-chevron-right"></i></button>
        </div>
    </div>

    <div class="search-filter-bar">
        <div class="search-input-group">
            <i class="fas fa-search"></i>
            <input type="text" id="search-activity-input" placeholder="Buscar actividade, instrutor...">
        </div>
        <div class="filter-icons">
            <button id="favorites-btn" title="Mostrar Favoritos"><i class="far fa-heart"></i></button>
            <button id="filter-btn" title="Filtros" data-bs-toggle="modal" data-bs-target="#filterModal"><i class="fas fa-sliders-h"></i></button>
        </div>
    </div>

    <div id="daily-classes-container">
        <!-- Class cards will be populated by JS -->
    </div>

    <!-- Modal Class Details -->
    <div class="modal fade" id="classDetailModal" tabindex="-1" aria-labelledby="classModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <img id="classModalBannerImage" src="assets/img/class_banners/default_banner.jpg" alt="Detalhes da Aula" style="width: 100%; height: 250px; object-fit: cover; border-top-left-radius: var(--bs-modal-border-radius); border-top-right-radius: var(--bs-modal-border-radius);">
                <div class="modal-header" id="classModalHeader" style="border-bottom-width: 2px;">
                    <h5 class="modal-title" id="classModalLabel">Detalhes da Aula</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-7">
                            <div class="mb-3">
                                <h6 class="text-primary mb-1"><i class="fas fa-calendar-alt me-2"></i>Horário e Duração</h6>
                                <p id="classModalTime" class="lead fs-6 mb-0 text-dark"></p>
                            </div>
                            <div class="mb-3">
                                <h6 class="text-primary mb-1"><i class="fas fa-chalkboard-teacher me-2"></i>Instrutor</h6>
                                <div class="d-flex align-items-center">
                                    <a href="#" id="classModalTrainerLink" class="text-decoration-none" title="">
                                        <img id="classModalTrainerAvatar" src="assets/img/avatars/default.png" alt="Instrutor" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover; border: 1px solid #ccc;">
                                    </a>
                                </div>
                            </div>
                            <div class="mb-3">
                                <h6 class="text-primary mb-1"><i class="fas fa-info-circle me-2"></i>Descrição</h6>
                                <p id="classModalDescription" class="text-muted small"></p>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="mb-3">
                                <h6 class="text-primary mb-1">
                                    <i class="fas fa-users me-2"></i>Participantes
                                    <span class="badge bg-secondary ms-1 rounded-pill">
                                        <span id="classModalParticipantCount">0</span>/<span id="classModalMaxParticipants">0</span>
                                    </span>
                                </h6>
                                <div id="classModalParticipantsListContainer" style="max-height: 180px; overflow-y: auto;">
                                    <ul class="list-inline" id="classModalParticipantsList">
                                        <!-- Participant list items will be injected here -->
                                    </ul>
                                </div>
                            </div>
                            <div id="classModalStatus" class="mt-3 text-center">
                                <!-- Status like 'Enrolled', 'Full', 'Finished' -->
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i>Fechar</button>
                    <button type="button" class="btn btn-primary" id="classModalEnrollButton" style="display: none;"><i class="fas fa-user-plus me-1"></i>Inscrever-se</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Filtros -->
    <div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="filterModalLabel">Filtrar Aulas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="filter-modal-section">
                        <h6>Tipo de Aula</h6>
                        <div id="filter-class-types-container" class="filter-options-container">
                            <!-- Checkboxes de tipo de aula serão inseridos aqui -->
                        </div>
                    </div>
                    <div class="filter-modal-section">
                        <h6>Instrutor</h6>
                        <div id="filter-trainers-container" class="filter-options-container">
                            <!-- Checkboxes de instrutor serão inseridos aqui -->
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" id="clear-filters-btn">Limpar Filtros</button>
                    <button type="button" class="btn btn-primary" id="apply-filters-btn">Aplicar Filtros</button>
                </div>
            </div>
        </div>
    </div>

</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const monthYearDisplay = document.getElementById('month-year-display');
    const daysStrip = document.getElementById('days-strip');
    const prevWeekBtn = document.getElementById('prev-week-btn');
    const nextWeekBtn = document.getElementById('next-week-btn');
    const dailyClassesContainer = document.getElementById('daily-classes-container');
    const searchInput = document.getElementById('search-activity-input');
    const favoritesBtn = document.getElementById('favorites-btn');
    const filterBtn = document.getElementById('filter-btn'); // Botão principal de filtros
    const filterModalEl = document.getElementById('filterModal');
    const applyFiltersBtn = document.getElementById('apply-filters-btn');
    const clearFiltersBtn = document.getElementById('clear-filters-btn');
    const filterClassTypesContainer = document.getElementById('filter-class-types-container');
    const filterTrainersContainer = document.getElementById('filter-trainers-container');


    let currentSelectedDate = new Date();
    let currentWeekStart = getMonday(new Date());

    const allClasses = <?php echo $all_classes_json; ?>;
    let showOnlyFavorites = false;
    let activeFilters = { classNames: [], trainerNames: [] };
    let uniqueClassNames = [];
    let uniqueTrainerNames = [];
    // Mapas para garantir nomes de label originais e values normalizados
    const uniqueNormalizedClassNames = new Map();
    const uniqueNormalizedTrainerNames = new Map();


    // --- ALTERAÇÃO AQUI: Chaves do classTypeColors em minúsculas ---
    const classTypeColors = {
        'sala de musculação': '#ADD8E6', // Azul Claro
        'localizada': '#FF0000',       // Vermelho
        'pilates': '#32CD32',          // Verde Lima
        'cycling': '#FFFF00',          // Amarelo
        'equilibriofit': '#4A4A4A',    // Cinza Escuro
        'bumbum': '#E91E63',           // Rosa
        'hip-hop': '#800080',          // Roxo
        'zumba': '#FFA500',            // Laranja
        'yoga': '#20B2AA',             // Verde Água / Turquesa Claro
        'default': '#B0B0B0'           // Cinza Padrão
    };


    function getMonday(d) {
        d = new Date(d);
        var day = d.getDay(),
            diff = d.getDate() - day + (day === 0 ? -6 : 1);
        return new Date(d.setDate(diff));
    }

    function formatDate(date, format = "YYYY-MM-DD") {
        const year = date.getFullYear();
        const month = (date.getMonth() + 1).toString().padStart(2, '0');
        const day = date.getDate().toString().padStart(2, '0');
        if (format === "YYYY-MM-DD") {
            return `${year}-${month}-${day}`;
        }
        return date;
    }

    function renderWeekSelector() {
        daysStrip.innerHTML = '';
        const tempDate = new Date(currentWeekStart);
        const monthNames = ["Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho",
                            "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"];
        monthYearDisplay.textContent = `${monthNames[tempDate.getMonth()]} ${tempDate.getFullYear()}`;

        const dayAbbreviations = ["DOM", "SEG", "TER", "QUA", "QUI", "SEX", "SÁB"];

        for (let i = 0; i < 7; i++) {
            const dayDate = new Date(currentWeekStart);
            dayDate.setDate(currentWeekStart.getDate() + i);

            const dayItem = document.createElement('div');
            dayItem.classList.add('day-item');
            dayItem.dataset.date = formatDate(dayDate);

            const dayAbbr = document.createElement('span');
            dayAbbr.classList.add('day-abbr');
            dayAbbr.textContent = dayAbbreviations[dayDate.getDay()];

            const dayNumber = document.createElement('span');
            dayNumber.classList.add('day-number');
            dayNumber.textContent = dayDate.getDate();

            dayItem.appendChild(dayAbbr);
            dayItem.appendChild(dayNumber);

            if (formatDate(dayDate) === formatDate(currentSelectedDate)) {
                dayItem.classList.add('selected');
            }

            dayItem.addEventListener('click', function() {
                currentSelectedDate = new Date(this.dataset.date + "T00:00:00");
                renderWeekSelector();
                renderDailyClasses();
            });
            daysStrip.appendChild(dayItem);
        }
    }

    function getInstructorInitials(name) {
        if (!name) return '';
        const parts = name.trim().split(' ');
        if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }

    function populateFilterModal() {
        uniqueNormalizedClassNames.clear();
        uniqueNormalizedTrainerNames.clear();

        allClasses.forEach(cls => {
            if (cls.name) {
                const normalized = cls.name.trim().toLowerCase();
                if (!uniqueNormalizedClassNames.has(normalized)) {
                    uniqueNormalizedClassNames.set(normalized, cls.name.trim()); // Key: normalized, Value: original for label
                }
            }
            if (cls.trainer_name) {
                const normalized = cls.trainer_name.trim().toLowerCase();
                if (!uniqueNormalizedTrainerNames.has(normalized)) {
                    uniqueNormalizedTrainerNames.set(normalized, cls.trainer_name.trim());
                }
            }
        });

        filterClassTypesContainer.innerHTML = '';
        const sortedClassKeys = Array.from(uniqueNormalizedClassNames.keys()).sort();
        sortedClassKeys.forEach(normalizedName => {
            const originalName = uniqueNormalizedClassNames.get(normalizedName);
            const div = document.createElement('div');
            div.classList.add('form-check');
            const checkboxId = `filter-class-${normalizedName.replace(/[^a-zA-Z0-9-]/g, '')}`;
            div.innerHTML = `
                <input class="form-check-input" type="checkbox" value="${normalizedName}" id="${checkboxId}">
                <label class="form-check-label" for="${checkboxId}">
                    ${originalName}
                </label>
            `;
            filterClassTypesContainer.appendChild(div);
        });

        filterTrainersContainer.innerHTML = '';
        const sortedTrainerKeys = Array.from(uniqueNormalizedTrainerNames.keys()).sort();
        sortedTrainerKeys.forEach(normalizedName => {
            const originalName = uniqueNormalizedTrainerNames.get(normalizedName);
            const div = document.createElement('div');
            div.classList.add('form-check');
            const checkboxId = `filter-trainer-${normalizedName.replace(/[^a-zA-Z0-9-]/g, '')}`;
            div.innerHTML = `
                <input class="form-check-input" type="checkbox" value="${normalizedName}" id="${checkboxId}">
                <label class="form-check-label" for="${checkboxId}">
                    ${originalName}
                </label>
            `;
            filterTrainersContainer.appendChild(div);
        });
    }


    function renderDailyClasses() {
        dailyClassesContainer.innerHTML = '';
        const selectedDateStr = formatDate(currentSelectedDate);
        const searchTerm = searchInput.value.toLowerCase();

        const classesForSelectedDay = allClasses.filter(cls => {
            const classDateStr = cls.schedule.substring(0, 10);
            if (classDateStr !== selectedDateStr) return false;

            // Filtro de pesquisa
            const matchesSearch = searchTerm === '' ||
                                  cls.name.toLowerCase().includes(searchTerm) ||
                                  (cls.trainer_name && cls.trainer_name.toLowerCase().includes(searchTerm)) ||
                                  (cls.description && cls.description.toLowerCase().includes(searchTerm));
            if (!matchesSearch) return false;

            // Filtro de favoritos
            if (showOnlyFavorites && !cls.is_favorite) return false;

            // Filtros gerais (tipo de aula, instrutor)
            if (activeFilters.classNames.length > 0) {
                const currentClassNameNormalized = cls.name ? cls.name.trim().toLowerCase() : null;
                if (!currentClassNameNormalized || !activeFilters.classNames.includes(currentClassNameNormalized)) {
                    return false;
                }
            }
            if (activeFilters.trainerNames.length > 0) {
                const currentTrainerNameNormalized = cls.trainer_name ? cls.trainer_name.trim().toLowerCase() : null;
                if (!currentTrainerNameNormalized || !activeFilters.trainerNames.includes(currentTrainerNameNormalized)) {
                    return false;
                }
            }

            return true;
        });

        if (classesForSelectedDay.length === 0) {
            let message = `Nenhuma aula encontrada para ${selectedDateStr}`;
            if (searchTerm) message += ` com o termo "${searchTerm}"`;
            if (showOnlyFavorites) message += ` (apenas favoritos)`;
            if (activeFilters.classNames.length > 0 || activeFilters.trainerNames.length > 0) message += ` com os filtros aplicados`;
            message += `.`;
            dailyClassesContainer.innerHTML = `<p class="no-classes">${message}</p>`;
            return;
        }

        classesForSelectedDay.sort((a, b) => new Date(a.schedule) - new Date(b.schedule));

        classesForSelectedDay.forEach(cls => {
            const card = document.createElement('div');
            card.classList.add('class-card');
            card.dataset.classId = cls.id; // Para fácil acesso

            const typeBand = document.createElement('div');
            typeBand.classList.add('class-type-band');
            // --- ALTERAÇÃO AQUI: Usar nome normalizado (minúsculas) para buscar a cor ---
            typeBand.style.backgroundColor = classTypeColors[cls.name.trim().toLowerCase()] || classTypeColors['default'];

            const content = document.createElement('div');
            content.classList.add('class-card-content');

            const scheduleDate = new Date(cls.schedule);
            const endDate = new Date(scheduleDate.getTime() + cls.duration * 60000);
            const timeStr = `${scheduleDate.getHours().toString().padStart(2, '0')}:${scheduleDate.getMinutes().toString().padStart(2, '0')}–${endDate.getHours().toString().padStart(2, '0')}:${endDate.getMinutes().toString().padStart(2, '0')}`;
            const classColor = classTypeColors[cls.name.trim().toLowerCase()] || classTypeColors['default']; // Também normalizado aqui


            let instructorHtml = '';
            if (cls.trainer_name) {
                let avatarContent = getInstructorInitials(cls.trainer_name);
                if (cls.trainer_avatar && cls.trainer_avatar !== 'default.png' && cls.trainer_avatar !== null) {
                    avatarContent = `<img src="assets/img/profile_pics/${cls.trainer_avatar}" alt="${cls.trainer_name}">`;
                }
                instructorHtml = `
                    <div class="instructor-bubbles">
                        <div class="instructor-bubble" title="${cls.trainer_name}">${avatarContent}</div>
                    </div>`;
            }

            let spotsHtml = '';
            if (!cls.aula_passou) {
                spotsHtml = `<div class="spots-badge">Vagas: ${cls.vagas_disponiveis}</div>`;
            }

            // Elemento para o status da aula (Finalizada, Inscrito)
            let statusElement = null;
            if (cls.aula_passou) {
                statusElement = document.createElement('div');
                statusElement.classList.add('status-finished');
                statusElement.textContent = 'Finalizada';
            } else if (cls.is_enrolled) {
                statusElement = document.createElement('div');
                // Adicionada classe class-status-text para poder ser encontrado e removido/atualizado
                statusElement.classList.add('status-finished', 'text-success', 'class-status-text');
                statusElement.textContent = 'Inscrito';
            }

            // Ícone de favorito
            const favoriteIcon = document.createElement('i');
            favoriteIcon.classList.add('favorite-icon', cls.is_favorite ? 'fas' : 'far', 'fa-heart');
            if(cls.is_favorite) favoriteIcon.classList.add('is-favorite');
            favoriteIcon.title = cls.is_favorite ? "Remover dos Favoritos" : "Adicionar aos Favoritos";
            favoriteIcon.style.zIndex = "2"; // Para garantir que fica acima de outros elementos se houver sobreposição
           favoriteIcon.addEventListener('click', (e) => {
                e.stopPropagation(); // Impede que o clique no coração abra o modal da aula
                toggleFavorite(cls.id, favoriteIcon);
            });

            // Wrapper para info clicável
            const mainInfoWrapper = document.createElement('div');
            mainInfoWrapper.classList.add('class-card-main-info');
            mainInfoWrapper.innerHTML = `
                ${spotsHtml}
                <div class="class-time">${timeStr}</div>
                <div class="class-name">${cls.name}</div>
                ${instructorHtml}
            `;
            if (statusElement) { // Adiciona o elemento de status se existir
                mainInfoWrapper.appendChild(statusElement);
            }
            mainInfoWrapper.addEventListener('click', () => openClassModal(cls, classColor));

            content.appendChild(mainInfoWrapper);

            // Botão de Cancelamento (fora do mainInfoWrapper, dentro do content)
            if (cls.is_enrolled && !cls.aula_passou) {
                const cancelButtonElement = document.createElement('button');
                cancelButtonElement.classList.add('btn', 'btn-sm', 'btn-outline-danger', 'cancel-enrollment-btn', 'mt-1'); // Removido 'w-100'
                cancelButtonElement.textContent = 'Cancelar Inscrição';
                cancelButtonElement.addEventListener('click', (e) => {
                    e.stopPropagation(); // Impede que o clique no botão abra o modal da aula
                    cancelEnrollment(cls.id, card); // Passa o elemento card
                });
                content.appendChild(cancelButtonElement);
            }

            content.appendChild(favoriteIcon); // Adiciona o ícone de favorito

            card.appendChild(typeBand);
            card.appendChild(content);
            dailyClassesContainer.appendChild(card);
        });
    }

    function openClassModal(cls, classColor) {
        document.getElementById('classModalLabel').textContent = cls.name;
        // --- ALTERAÇÃO AQUI: Usar nome normalizado (minúsculas) para buscar a cor ---
        document.getElementById('classModalHeader').style.backgroundColor = classTypeColors[cls.name.trim().toLowerCase()] || classTypeColors['default'];

        const classModalBannerImage = document.getElementById('classModalBannerImage');
        const defaultBannerSrc = 'assets/img/class_banners/default_banner.jpg';

        let cacheBuster = new Date().getTime();
        let specificBannerFilename = cls.name.toLowerCase().replace(/\s+/g, '-') + '.png';
        let specificBannerSrc = `assets/img/class_banners/${specificBannerFilename}?v=${cacheBuster}`;

        console.log(`Tentando carregar banner para a aula: '${cls.name}'`);
        console.log(`Caminho do banner específico gerado: '${specificBannerSrc}'`);

        classModalBannerImage.src = specificBannerSrc;

        classModalBannerImage.onerror = function() {
            console.error(`Erro ao carregar o banner específico: '${specificBannerSrc}'. A carregar banner padrão: '${defaultBannerSrc}'`);
            this.src = defaultBannerSrc;
            this.onerror = null;
        };

        // --- ALTERAÇÃO AQUI: Usar nome normalizado (minúsculas) para determinar se a cor é escura ---
        const isDark = tinycolor(classTypeColors[cls.name.trim().toLowerCase()] || classTypeColors['default']).isDark();
        document.getElementById('classModalHeader').style.color = isDark ? '#FFFFFF' : '#000000';
        // Adicionar dataset ao botão de inscrever no modal para referência
        document.getElementById('classModalEnrollButton').dataset.classIdForModal = cls.id;
        document.querySelector('#classModalHeader .btn-close').style.filter = isDark ? 'invert(1) grayscale(100%) brightness(200%)' : '';

        const scheduleDate = new Date(cls.schedule);
        const endDate = new Date(scheduleDate.getTime() + cls.duration * 60000);
        const timeStr = `${scheduleDate.getHours().toString().padStart(2, '0')}:${scheduleDate.getMinutes().toString().padStart(2, '0')}–${endDate.getHours().toString().padStart(2, '0')}:${endDate.getMinutes().toString().padStart(2, '0')}`;
        document.getElementById('classModalTime').textContent = `${timeStr} (${cls.duration} min)`;

        const trainerAvatarElement = document.getElementById('classModalTrainerAvatar');
        const trainerLinkElement = document.getElementById('classModalTrainerLink');

        if (cls.trainer_avatar && cls.trainer_avatar !== 'default.png' && cls.trainer_avatar !== null) {
            trainerAvatarElement.src = `assets/img/profile_pics/${cls.trainer_avatar}`;
        } else {
            trainerAvatarElement.src = 'assets/img/avatars/default.png';
        }
        trainerAvatarElement.alt = cls.trainer_name || 'Instrutor';
        trainerLinkElement.href = `profile_view.php?user_id=${cls.trainer_id}`;
        trainerLinkElement.title = cls.trainer_name || 'Ver Perfil do Instrutor';

        document.getElementById('classModalDescription').textContent = cls.description || 'Sem descrição detalhada.';
        document.getElementById('classModalMaxParticipants').textContent = cls.max_participants;
        document.getElementById('classModalParticipantCount').textContent = cls.current_participants;

        const participantsList = document.getElementById('classModalParticipantsList');
        participantsList.innerHTML = '';
        if (cls.participants && cls.participants.length > 0) {
            document.getElementById('classModalParticipantsListContainer').style.maxHeight = '180px';
            cls.participants.forEach(participant => {
                const li = document.createElement('li');
                li.classList.add('list-inline-item');
                let avatarSrc = (participant.profile_picture && participant.profile_picture !== 'default.png' && participant.profile_picture !== null) ? `assets/img/profile_pics/${participant.profile_picture}` : 'assets/img/avatars/default.png';
                li.innerHTML = `
                    <a href="profile_view.php?user_id=${participant.id}" class="text-decoration-none" title="${participant.name}">
                        <img src="${avatarSrc}" alt="${participant.name}" class="rounded-circle" style="width: 35px; height: 35px; object-fit: cover; margin: 2px; border: 1px solid #ddd;">
                    </a>
                `;
                participantsList.appendChild(li);
            });
        } else {
             document.getElementById('classModalParticipantsListContainer').style.maxHeight = 'auto';
             participantsList.innerHTML = '<li class="list-inline-item"><small class="text-muted">Nenhum participante inscrito ainda.</small></li>';
        }

        const enrollButton = document.getElementById('classModalEnrollButton');
        enrollButton.style.display = 'none';
        const modalStatus = document.getElementById('classModalStatus');
        modalStatus.innerHTML = '';

        if (!cls.aula_passou) {
            if (cls.is_enrolled) {
                modalStatus.innerHTML = `<span class="badge bg-success fs-6">Você está inscrito!</span>`;
            } else if (cls.vagas_disponiveis > 0) {
                enrollButton.style.display = 'block';
                enrollButton.textContent = `Inscrever-se (${cls.vagas_disponiveis} vagas)`;
                enrollButton.onclick = () => {
                    if(confirm(`Deseja inscrever-se na aula de ${cls.name} às ${timeStr}?`)){
                        window.location.href = `enroll.php?class_id=${cls.id}`;
                    }
                };
                modalStatus.innerHTML = `<span class="badge bg-info fs-6">${cls.vagas_disponiveis} vagas disponíveis</span>`;
            } else {
                modalStatus.innerHTML = `<span class="badge bg-danger fs-6">Aula Esgotada</span>`;
            }
        } else {
            modalStatus.innerHTML = `<span class="badge bg-secondary fs-6">Aula Terminada</span>`;
        }

        var classModal = new bootstrap.Modal(document.getElementById('classDetailModal'));
        classModal.show();
    }

    async function toggleFavorite(classId, iconElement) {
        const formData = new FormData();
        formData.append('class_id', classId);

        try {
            const response = await fetch('toggle_favorite.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.status === 'success') {
                // Atualizar o estado no array allClasses
                const classIndex = allClasses.findIndex(cls => cls.id === classId);
                if (classIndex !== -1) {
                    allClasses[classIndex].is_favorite = result.is_favorite;
                }

                // Atualizar o ícone visualmente
                iconElement.classList.toggle('far', !result.is_favorite);
                iconElement.classList.toggle('fas', result.is_favorite);
                iconElement.classList.toggle('is-favorite', result.is_favorite);
                iconElement.title = result.is_favorite ? "Remover dos Favoritos" : "Adicionar aos Favoritos";

                // Se estiver a mostrar apenas favoritos e a aula foi desfavoritada, re-renderizar
                if (showOnlyFavorites && !result.is_favorite) {
                    renderDailyClasses();
                }
            } else {
                alert('Erro: ' + result.message);
            }
        } catch (error) {
            console.error('Erro ao favoritar:', error);
            alert('Erro de comunicação ao tentar favoritar.');
        }
    }

    async function cancelEnrollment(classId, cardElement) {
        if (!confirm('Tem a certeza que deseja cancelar a sua inscrição nesta aula?')) {
            return;
        }

        const formData = new FormData();
        formData.append('class_id', classId);

        try {
            const response = await fetch('unenroll_class.php', { // Alterado para o ficheiro correto
                method: 'POST',
                body: formData
            });

            const responseText = await response.text(); // Obter a resposta como texto primeiro

            if (!response.ok) {
                // Se a resposta HTTP não for OK (ex: 404, 500)
                console.error('Erro HTTP ao cancelar:', response.status, response.statusText);
                console.error('Resposta do servidor (texto):', responseText);
                throw new Error(`Falha na comunicação com o servidor (HTTP ${response.status}). Resposta: ${responseText.substring(0,100)}...`);
            }

            let result;
            try {
                result = JSON.parse(responseText); // Tentar parsear o texto como JSON
            } catch (jsonError) {
                // Se a resposta não for JSON válido
                console.error('Erro ao parsear JSON da resposta:', jsonError);
                console.error('Resposta recebida (não é JSON válido):', responseText);
                throw new Error(`Resposta inválida do servidor. Conteúdo: ${responseText.substring(0,100)}...`);
            }

            // Se chegou aqui, response.ok era true e o JSON foi parseado com sucesso
            if (result.status === 'success') {
                // Lógica de sucesso existente (atualizar UI, etc.)
                // ... (o seu código de sucesso continua aqui) ...
                // Atualizar o estado no array allClasses

                const classIndex = allClasses.findIndex(cls => cls.id === classId);
                if (classIndex !== -1) {
                    allClasses[classIndex].is_enrolled = false;
                    allClasses[classIndex].current_participants--; // Assumindo que o backend não retorna este valor
                    allClasses[classIndex].vagas_disponiveis++;   // Assumindo que o backend não retorna este valor

                    // Atualizar o card na UI
                    const spotsBadge = cardElement.querySelector('.spots-badge');
                    if (spotsBadge && allClasses[classIndex].vagas_disponiveis >= 0) { // Só atualiza se houver vagas
                        spotsBadge.textContent = `Vagas: ${allClasses[classIndex].vagas_disponiveis}`;
                    }

                    const statusTextElement = cardElement.querySelector('.class-status-text');
                    if (statusTextElement) {
                        statusTextElement.remove(); // Remove o texto "Inscrito"
                    }

                    const cancelButton = cardElement.querySelector('.cancel-enrollment-btn');
                    if (cancelButton) {
                        cancelButton.remove();
                    }

                    // Atualizar o modal se estiver aberto para esta aula
                    const classDetailModal = document.getElementById('classDetailModal');
                    const modalInstance = bootstrap.Modal.getInstance(classDetailModal);

                    if (modalInstance && classDetailModal.classList.contains('show')) {
                        const modalClassId = parseInt(document.getElementById('classModalEnrollButton').dataset.classIdForModal);
                        if (modalClassId === classId) {
                            // Re-chamar openClassModal com os dados atualizados para refrescar o modal completamente
                            openClassModal(allClasses[classIndex], classTypeColors[allClasses[classIndex].name.trim().toLowerCase()] || classTypeColors['default']);
                        }
                    }
                }
                alert('Inscrição cancelada com sucesso!');
            } else {
                // Erro reportado pelo servidor (JSON válido, mas status === 'error')
                console.error('Erro reportado pelo servidor ao cancelar:', result.message);
                alert('Erro ao cancelar inscrição: ' + result.message);
            }
        } catch (error) { // Este catch agora apanha erros de rede, HTTP, e JSON parsing
            console.error('Erro geral na operação de cancelamento:', error);
            // Mostra uma mensagem de erro mais detalhada no alerta
            alert('Erro de comunicação ao tentar cancelar a inscrição. Detalhe: ' + error.message);
        }
    }

    prevWeekBtn.addEventListener('click', function() {
        currentWeekStart.setDate(currentWeekStart.getDate() - 7);
        if (currentSelectedDate >= new Date(currentWeekStart.getTime() + 7 * 24 * 60 * 60 * 1000) || currentSelectedDate < currentWeekStart) {
            currentSelectedDate = new Date(currentWeekStart);
        }
        renderWeekSelector();
        renderDailyClasses();
    });

    nextWeekBtn.addEventListener('click', function() {
        currentWeekStart.setDate(currentWeekStart.getDate() + 7);
        if (currentSelectedDate >= new Date(currentWeekStart.getTime() + 7 * 24 * 60 * 60 * 1000) || currentSelectedDate < currentWeekStart) {
            currentSelectedDate = new Date(currentWeekStart);
        }
        renderWeekSelector();
        renderDailyClasses();
    });

    searchInput.addEventListener('input', renderDailyClasses);

    favoritesBtn.addEventListener('click', function() {
        showOnlyFavorites = !showOnlyFavorites;
        this.classList.toggle('active', showOnlyFavorites);
        this.querySelector('i').classList.toggle('far', !showOnlyFavorites);
        this.querySelector('i').classList.toggle('fas', showOnlyFavorites);
        this.title = showOnlyFavorites ? "Mostrar Todas as Aulas" : "Mostrar Favoritos";
        renderDailyClasses();
    });

    applyFiltersBtn.addEventListener('click', function() {
        activeFilters.classNames = [];
        filterClassTypesContainer.querySelectorAll('input[type="checkbox"]:checked').forEach(cb => {
            activeFilters.classNames.push(cb.value);
        });

        activeFilters.trainerNames = [];
        filterTrainersContainer.querySelectorAll('input[type="checkbox"]:checked').forEach(cb => {
            activeFilters.trainerNames.push(cb.value);
        });

        const modalInstance = bootstrap.Modal.getInstance(filterModalEl);
        if (modalInstance) modalInstance.hide();
        renderDailyClasses();
    });

    clearFiltersBtn.addEventListener('click', function() {
        activeFilters = { classNames: [], trainerNames: [] };
        filterClassTypesContainer.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
        filterTrainersContainer.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);

        const modalInstance = bootstrap.Modal.getInstance(filterModalEl);
        if (modalInstance) modalInstance.hide();
        renderDailyClasses();
    });


    // Initial render
    renderWeekSelector();
    populateFilterModal(); // Popula o modal de filtros
    renderDailyClasses();
});

</script>

<?php
include 'footer.php';
?>
