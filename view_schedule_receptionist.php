<?php
session_start();

// Verifica se o usuário está logado e se é recepcionista
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'receptionist') {
    header('Location: login.php');
    exit;
}

// Inclui a conexão com o banco de dados
include 'db.php';

// Inclui o cabeçalho da página
include 'header.php';

// Define qual link da sidebar deve estar ativo
$active_page = basename($_SERVER['PHP_SELF']); // view_schedule_receptionist.php

// Inclui a sidebar do recepcionista
include 'sidebar_receptionist.php';

// --- Preparar dados das aulas ---
$all_classes_json = '[]'; // Will store all class data for JS
$classes_data = [];

try {
    // Busca todas as aulas disponíveis com contagem de participantes e nome do instrutor
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
            (SELECT COUNT(*) FROM class_participants cp WHERE cp.class_id = c.id) AS current_participants
        FROM classes c
        JOIN users u ON c.trainer_id = u.id WHERE c.schedule >= NOW() -- Apenas aulas futuras
        ORDER BY c.schedule
    ");
    // Não precisa do user_id aqui, pois não há status de inscrição ou favorito para o recepcionista
    $stmt->execute();
    $classes = $stmt->fetchAll();

    if ($classes) {
        $temp_classes_data = [];
        foreach ($classes as $class_item) {
            $vagas_disponiveis = $class_item['max_participants'] - $class_item['current_participants'];
            $data_aula_ts = strtotime($class_item['schedule']);
            $aula_passou = $data_aula_ts < time(); // Esta verificação ainda pode ser útil para o modal, embora a query principal já filtre

            // Fetch participants for this class (optional for modal)
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
                'participants' => $participants
            ];
        }
        // Converte o array PHP para JSON para uso no JavaScript
        $all_classes_json = json_encode($temp_classes_data);
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar aulas para recepcionista: " . $e->getMessage());
    echo "<div class='alert alert-danger'>Erro ao carregar dados das aulas.</div>";
}

?>

<!-- Reutiliza estilos da página classes.php -->
<style>
    /* Copiar estilos relevantes de classes.php aqui ou garantir que dashboard_cliente.css os inclui */
    /* classes-header, week-selector-container, month-year-display, week-navigation,
       days-strip, day-item, search-filter-bar, search-input-group, filter-icons,
       daily-classes-container, class-card, class-type-band, class-card-content,
       class-card-main-info, class-time, class-name, instructor-bubbles, instructor-bubble,
       spots-badge, status-finished, no-classes, filter-modal-section, filter-options-container,
       .filter-options-container .form-check-label, #filterModalLabel, .filter-modal-section h6
       ... e estilos do modal de detalhes da aula (classDetailModal)
    */
    /* Exemplo: */
     .classes-header { display: flex; align-items: center; padding: 10px 15px; background-color: var(--bs-body-bg); border-bottom: 1px solid var(--bs-border-color); position: sticky; top: 0; z-index: 1000; }
     .classes-header .back-button { font-size: 1.2rem; color: var(--bs-body-color); text-decoration: none; margin-right: 15px; } /* Manter se quiser um botão de voltar */
     .classes-header .page-title { font-size: 1.25rem; font-weight: 500; color: var(--bs-body-color); margin: 0; } /* Ajustar margin */
     .week-navigation button { background: none; border: none; font-size: 1.2rem; color: var(--bs-primary); cursor: pointer; padding: 5px 10px; }
     .days-strip { display: flex; overflow-x: auto; justify-content: center; flex-grow: 1; padding: 5px 0; }
     .day-item { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 8px 5px; min-width: 45px; cursor: pointer; border-radius: 50%; transition: background-color 0.3s, color 0.3s; margin: 0 2px; }
     .day-item .day-abbr { font-size: 0.7rem; font-weight: 500; text-transform: uppercase; color: var(--bs-secondary-color); }
     .day-item .day-number { font-size: 1rem; font-weight: 500; color: var(--bs-body-color); }
     .day-item.selected { background-color: #FFA500; }
     .day-item.selected .day-abbr, .day-item.selected .day-number { color: #FFFFFF; }
     .day-item:not(.selected):hover { background-color: var(--bs-tertiary-bg); }
     .search-filter-bar { display: flex; align-items: center; padding: 10px 15px; background-color: var(--bs-body-bg); border-bottom: 1px solid var(--bs-border-color); }
     .search-input-group { display: flex; align-items: center; background-color: var(--bs-tertiary-bg); border-radius: 20px; padding: 5px 10px; flex-grow: 1; }
     .search-input-group i { color: var(--bs-secondary-color); margin-right: 8px; }
     .search-input-group input { border: none; background: transparent; outline: none; width: 100%; color: var(--bs-body-color); }
     .search-input-group input::placeholder { color: var(--bs-secondary-color); }
     .filter-icons button { background: none; border: none; font-size: 1.2rem; color: var(--bs-secondary-color); margin-left: 10px; cursor: pointer; }
     .filter-icons button.active { color: var(--bs-primary); }
     #all-classes-container { padding: 15px; } /* ID do container alterado */
     .class-card { display: flex; background-color: var(--bs-body-bg); border: 1px solid var(--bs-border-color); border-radius: 8px; margin-bottom: 15px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
     .class-type-band { width: 8px; flex-shrink: 0; }
     .class-card-content { padding: 12px 15px; flex-grow: 1; position: relative; }
     .class-card-main-info { cursor: pointer; margin-bottom: 5px; }
     .class-time { font-size: 0.9rem; font-weight: 500; color: var(--bs-body-color); margin-bottom: 2px; }
     .class-name { font-size: 1.1rem; font-weight: bold; color: var(--bs-body-color); margin-bottom: 5px; }
     .instructor-bubbles { display: flex; gap: 5px; margin-bottom: 8px; }
     .instructor-bubble { width: 28px; height: 28px; border-radius: 50%; background-color: var(--bs-secondary-bg); color: var(--bs-secondary-color); display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 500; }
     .instructor-bubble img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
     .spots-badge { position: absolute; top: 12px; right: 15px; background-color: #007bff; color: white; font-size: 0.75rem; padding: 3px 8px; border-radius: 10px; }
     .status-finished { font-size: 0.8rem; color: var(--bs-secondary-color); margin-top: 5px; }
     .no-classes { text-align: center; padding: 20px; color: var(--bs-secondary-color); }
     .filter-modal-section { margin-bottom: 1rem; }
     .filter-modal-section h6 { margin-bottom: 0.5rem; font-weight: 500; }
     .filter-options-container { max-height: 200px; overflow-y: auto; padding: 5px; border: 1px solid var(--bs-border-color); border-radius: var(--bs-border-radius); }
     .filter-options-container .form-check-label { color: #000; }
     #filterModalLabel, .filter-modal-section h6 { color: #000 !important; }

    /* Estilos para Modal Class Details */
    #classDetailModal .modal-header { border-bottom-width: 2px; }
    #classDetailModal .modal-title { color: #000 !important; } /* Forçar preto no título do modal */
    #classDetailModal .modal-body h6 { color: var(--bs-primary) !important; } /* Cor dos títulos no body */
    #classDetailModal .modal-body p { color: var(--bs-body-color); } /* Cor do texto no body */
    #classDetailModal .modal-body .text-dark { color: var(--bs-body-color) !important; } /* Garantir cor do texto */
    #classDetailModal .modal-body .text-muted { color: var(--bs-secondary-color) !important; } /* Garantir cor do texto muted */
    #classDetailModal .modal-body .list-inline-item small { color: var(--bs-secondary-color) !important; } /* Cor da lista de participantes */

</style>

<!-- Conteúdo Principal -->
<main class="main-content">
    <div class="classes-header">
        <a href="receptionist.php" class="back-button"><i class="fas fa-arrow-left"></i></a>
        <h1 class="page-title">Horário de Aulas</h1>
    </div>

    <!-- Removemos o seletor de semana -->

    <div class="search-filter-bar">
        <div class="search-input-group">
            <!-- Adicionado ID para o input de pesquisa -->
            <i class="fas fa-search"></i>
            <input type="text" id="search-activity-input" placeholder="Buscar actividade, instrutor...">
        </div>
        <div class="filter-icons">
            <!-- Botão de favoritos removido -->
            <button id="filter-btn" title="Filtros" data-bs-toggle="modal" data-bs-target="#filterModal"><i class="fas fa-sliders-h"></i></button>
        </div>
    </div>

    <!-- Renomeado o ID do container para ser mais genérico -->
    <div id="all-classes-container">
        <!-- Class cards will be populated by JS -->
    </div>

    <!-- Modal Class Details (Reutilizado) -->
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
                                <!-- Status like 'Full', 'Finished' -->
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i>Fechar</button>
                    <!-- Botão de inscrição removido -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Filtros (Reutilizado) -->
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
    // Removidos elementos do seletor de semana
    const allClassesContainer = document.getElementById('all-classes-container'); // Renomeado
    const searchInput = document.getElementById('search-activity-input');
    // Botão de favoritos removido
    const filterBtn = document.getElementById('filter-btn');
    const filterModalEl = document.getElementById('filterModal');
    const applyFiltersBtn = document.getElementById('apply-filters-btn');
    const clearFiltersBtn = document.getElementById('clear-filters-btn');
    const filterClassTypesContainer = document.getElementById('filter-class-types-container');
    const filterTrainersContainer = document.getElementById('filter-trainers-container');

    // currentSelectedDate e currentWeekStart não são mais necessários para a renderização principal
    let currentWeekStart = getMonday(new Date()); // Ainda usado para getMonday, pode ser simplificado

    const allClasses = <?php echo $all_classes_json; ?>; // Dados das aulas do PHP
    // showOnlyFavorites removido
    let activeFilters = { classNames: [], trainerNames: [] };
    // uniqueClassNames e uniqueTrainerNames removidos, usar Mapas
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

    // Função auxiliar para obter a segunda-feira da semana
    function getMonday(d) {
        d = new Date(d);
        var day = d.getDay(),
            diff = d.getDate() - day + (day === 0 ? -6 : 1); // Ajuste para segunda-feira
        return new Date(d.setDate(diff));
    }

    // Função auxiliar para obter iniciais do instrutor
    function getInstructorInitials(name) {
        if (!name) return '';
        const parts = name.trim().split(' ');
        if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }

    // Função para popular o modal de filtros com tipos de aula e instrutores
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
            // Gera um ID seguro para o checkbox
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

    // Função para renderizar TODAS as aulas (futuras) com base nos filtros e pesquisa
    function renderClasses() { // Renomeada
        allClassesContainer.innerHTML = ''; // Limpa o container
        const searchTerm = searchInput.value.toLowerCase();

        const classesForDisplay = allClasses.filter(cls => { // Renomeada a variável local
            // Filtro de pesquisa (nome da aula, instrutor, descrição)
            const matchesSearch = searchTerm === '' ||
                                  cls.name.toLowerCase().includes(searchTerm) ||
                                  (cls.trainer_name && cls.trainer_name.toLowerCase().includes(searchTerm)) ||
                                  (cls.description && cls.description.toLowerCase().includes(searchTerm));
            if (!matchesSearch) return false;

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

            return true; // A aula passa por todos os filtros
        });

        // Se não houver aulas encontradas (após filtros)
        if (classesForDisplay.length === 0) {
            let message = `Nenhuma aula futura encontrada`;
            if (searchTerm) message += ` com o termo "${searchTerm}"`;
            if (activeFilters.classNames.length > 0 || activeFilters.trainerNames.length > 0) message += ` com os filtros aplicados`;
            message += `.`;
            allClassesContainer.innerHTML = `<p class="no-classes">${message}</p>`;
            return;
        }

        // Ordena as aulas por horário (já vem ordenado do PHP, mas pode ser útil se houver manipulação no JS)
        // classesForDisplay.sort((a, b) => new Date(a.schedule) - new Date(b.schedule));

        // Renderiza os cards das aulas
        classesForDisplay.forEach(cls => {
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
            
            // Formatar a data para exibição
            const day = scheduleDate.getDate().toString().padStart(2, '0');
            const month = (scheduleDate.getMonth() + 1).toString().padStart(2, '0'); // Meses são 0-indexados
            const year = scheduleDate.getFullYear();
            const dateStr = `${day}/${month}/${year}`;


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
            if (!cls.aula_passou) { // Embora todas sejam futuras, esta flag pode ser útil para o modal
                spotsHtml = `<div class="spots-badge">Vagas: ${cls.vagas_disponiveis}</div>`;
            }

            // Elemento para o status da aula (Finalizada)
            let statusElement = null;
            if (cls.aula_passou) { // Esta condição será sempre falsa devido ao filtro SQL, mas mantemos para consistência do modal
                statusElement = document.createElement('div');
                statusElement.classList.add('status-finished');
                statusElement.textContent = 'Finalizada';
            }
            // Status "Inscrito" removido, pois recepcionista não se inscreve

            // Wrapper para info clicável (abre modal)
            const mainInfoWrapper = document.createElement('div');
            mainInfoWrapper.classList.add('class-card-main-info');
            mainInfoWrapper.innerHTML = `
                ${spotsHtml}
                <div class="class-time">${dateStr} - ${timeStr}</div> <!-- Adicionada a data -->
                <div class="class-name">${cls.name}</div>
                ${instructorHtml}
            `;
            if (statusElement) { // Adiciona o elemento de status se existir
                mainInfoWrapper.appendChild(statusElement);
            }
            // Adiciona evento de clique para abrir o modal de detalhes
            mainInfoWrapper.addEventListener('click', () => openClassModal(cls));

            content.appendChild(mainInfoWrapper);

            // Botão de Cancelamento removido
            // Ícone de favorito removido

            card.appendChild(typeBand);
            card.appendChild(content); // Adiciona o conteúdo ao card
            allClassesContainer.appendChild(card); // Adiciona o card ao container principal
        });
    }

    // Função para abrir o modal de detalhes da aula
    function openClassModal(cls) {
        document.getElementById('classModalLabel').textContent = cls.name;
        // --- ALTERAÇÃO AQUI: Usar nome normalizado (minúsculas) para buscar a cor ---
        const classColor = classTypeColors[cls.name.trim().toLowerCase()] || classTypeColors['default'];
        document.getElementById('classModalHeader').style.backgroundColor = classColor;

        // Lógica para banner da imagem (reutilizada)
        const classModalBannerImage = document.getElementById('classModalBannerImage');
        const defaultBannerSrc = 'assets/img/class_banners/default_banner.jpg';
        let cacheBuster = new Date().getTime();
        let specificBannerFilename = cls.name.toLowerCase().replace(/\s+/g, '-') + '.png';
        let specificBannerSrc = `assets/img/class_banners/${specificBannerFilename}?v=${cacheBuster}`;
        classModalBannerImage.src = specificBannerSrc;
        classModalBannerImage.onerror = function() {
            this.src = defaultBannerSrc;
            this.onerror = null;
        };

        // --- ALTERAÇÃO AQUI: Usar nome normalizado (minúsculas) para determinar se a cor é escura ---
        const isDark = tinycolor(classColor).isDark();
        document.getElementById('classModalHeader').style.color = isDark ? '#FFFFFF' : '#000000';
        document.querySelector('#classModalHeader .btn-close').style.filter = isDark ? 'invert(1) grayscale(100%) brightness(200%)' : '';

        const scheduleDate = new Date(cls.schedule);
        const endDate = new Date(scheduleDate.getTime() + cls.duration * 60000);
        const timeStr = `${scheduleDate.getHours().toString().padStart(2, '0')}:${scheduleDate.getMinutes().toString().padStart(2, '0')}–${endDate.getHours().toString().padStart(2, '0')}:${endDate.getMinutes().toString().padStart(2, '0')}`;
        
        const day = scheduleDate.getDate().toString().padStart(2, '0');
        const month = (scheduleDate.getMonth() + 1).toString().padStart(2, '0');
        const year = scheduleDate.getFullYear();
        const dateStr = `${day}/${month}/${year}`;
        
        document.getElementById('classModalTime').textContent = `${dateStr} ${timeStr} (${cls.duration} min)`;

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

        const modalStatus = document.getElementById('classModalStatus');
        modalStatus.innerHTML = '';

        if (!cls.aula_passou) { // Esta flag será sempre verdadeira para as aulas listadas
             if (cls.vagas_disponiveis > 0) {
                modalStatus.innerHTML = `<span class="badge bg-info fs-6">${cls.vagas_disponiveis} vagas disponíveis</span>`;
            } else {
                modalStatus.innerHTML = `<span class="badge bg-danger fs-6">Aula Esgotada</span>`;
            }
        } else { // Esta condição será sempre falsa devido ao filtro SQL, mas mantemos para consistência do modal
            modalStatus.innerHTML = `<span class="badge bg-secondary fs-6">Aula Terminada</span>`;
        }

        var classModal = new bootstrap.Modal(document.getElementById('classDetailModal'));
        classModal.show();
    }

    // Removidos Event Listeners para navegação da semana

    // Event Listener para a pesquisa
    // Chama renderClasses em vez de renderDailyClasses
    searchInput.addEventListener('input', renderClasses);

    // Event Listeners para o modal de filtros
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
        renderClasses(); // Chama renderClasses
    });

    clearFiltersBtn.addEventListener('click', function() {
        activeFilters = { classNames: [], trainerNames: [] };
        filterClassTypesContainer.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
        filterTrainersContainer.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);

        const modalInstance = bootstrap.Modal.getInstance(filterModalEl);
        if (modalInstance) modalInstance.hide();
        renderClasses(); // Chama renderClasses
    });


    // Inicialização: Popula filtros e renderiza TODAS as aulas futuras
    populateFilterModal(); // Popula o modal de filtros
    renderClasses(); // Renderiza todas as aulas futuras inicialmente
});

</script>

<?php
include 'footer.php';
?>
