// c:\xampp\htdocs\Projeto_PAP\assets\js\theme.js

document.addEventListener('DOMContentLoaded', () => {
    const themeToggle = document.getElementById('theme-toggle');
    const sunIcon = document.querySelector('.theme-switch-wrapper .fa-sun');
    const moonIcon = document.querySelector('.theme-switch-wrapper .fa-moon');

    // --- Gerenciamento do Tema (Light/Dark) ---

    const applyTheme = (theme) => {
        if (theme === 'dark') {
            document.body.classList.add('dark-mode');
            if (themeToggle) themeToggle.checked = true;
            if (sunIcon) sunIcon.style.display = 'inline-block'; // Mostra sol no modo escuro
            if (moonIcon) moonIcon.style.display = 'none';      // Esconde lua no modo escuro
        } else {
            document.body.classList.remove('dark-mode');
            if (themeToggle) themeToggle.checked = false;
            if (sunIcon) sunIcon.style.display = 'none';       // Esconde sol no modo claro
            if (moonIcon) moonIcon.style.display = 'inline-block'; // Mostra lua no modo claro
        }
    };

    const toggleTheme = () => {
        const currentTheme = document.body.classList.contains('dark-mode') ? 'light' : 'dark';
        localStorage.setItem('theme', currentTheme);
        applyTheme(currentTheme);
    };

    // Aplica o tema ao carregar a página
    const savedTheme = localStorage.getItem('theme') || 'light'; // Padrão é 'light'
    applyTheme(savedTheme);

    // Listener para o botão de toggle
    if (themeToggle) {
        themeToggle.addEventListener('change', toggleTheme);
    }
    // Listeners para os ícones (se quiser que eles também troquem o tema)
    if (sunIcon) {
        sunIcon.addEventListener('click', () => {
            if (document.body.classList.contains('dark-mode')) { // Só troca se estiver no modo escuro
                toggleTheme();
            }
        });
    }
    if (moonIcon) {
         moonIcon.addEventListener('click', () => {
            if (!document.body.classList.contains('dark-mode')) { // Só troca se estiver no modo claro
                toggleTheme();
            }
        });
    }


    // --- Gerenciamento das Cores do Chat ---

    const applyChatColors = () => {
        const savedColors = JSON.parse(localStorage.getItem('chatColors') || '{}');
        const root = document.documentElement;

        // Aplica cores salvas ou volta ao padrão definido no CSS se não houver cor salva
        root.style.setProperty('--chat-sent-bg', savedColors.sentBg || null); // null remove a propriedade inline, usando o CSS
        root.style.setProperty('--chat-sent-text', savedColors.sentText || null);
        root.style.setProperty('--chat-received-bg', savedColors.receivedBg || null);
        root.style.setProperty('--chat-received-text', savedColors.receivedText || null);
        // Opcional: Aplicar também aos timestamps se quiser customizar
        // root.style.setProperty('--chat-timestamp-sent', savedColors.timestampSent || null);
        // root.style.setProperty('--chat-timestamp-received', savedColors.timestampReceived || null);

        // Atualiza os valores dos inputs de cor na página de perfil (se existirem)
        const sentBgInput = document.getElementById('chatSentBg');
        const sentTextInput = document.getElementById('chatSentText');
        const receivedBgInput = document.getElementById('chatReceivedBg');
        const receivedTextInput = document.getElementById('chatReceivedText');

        if (sentBgInput) sentBgInput.value = savedColors.sentBg || getComputedStyle(root).getPropertyValue('--chat-sent-bg').trim();
        if (sentTextInput) sentTextInput.value = savedColors.sentText || getComputedStyle(root).getPropertyValue('--chat-sent-text').trim();
        if (receivedBgInput) receivedBgInput.value = savedColors.receivedBg || getComputedStyle(root).getPropertyValue('--chat-received-bg').trim();
        if (receivedTextInput) receivedTextInput.value = savedColors.receivedText || getComputedStyle(root).getPropertyValue('--chat-received-text').trim();
    };

    // Aplica as cores do chat ao carregar a página
    applyChatColors();

    // Listeners para os inputs de cor (serão adicionados na página de perfil)
    // A lógica de salvar no localStorage estará lá

    // Listener para o botão de reset (será adicionado na página de perfil)
    // A lógica de remover do localStorage estará lá
});

// Função auxiliar para ser chamada pela página de perfil
function updateChatColor(variableName, color) {
    document.documentElement.style.setProperty(variableName, color);
    // Salva no localStorage
    const savedColors = JSON.parse(localStorage.getItem('chatColors') || '{}');
    // Mapeia o nome da variável CSS para a chave no objeto salvo
    const keyMap = {
        '--chat-sent-bg': 'sentBg',
        '--chat-sent-text': 'sentText',
        '--chat-received-bg': 'receivedBg',
        '--chat-received-text': 'receivedText'
    };
    const storageKey = keyMap[variableName];
    if (storageKey) {
        savedColors[storageKey] = color;
        localStorage.setItem('chatColors', JSON.stringify(savedColors));
    }
}

// Função auxiliar para resetar cores
function resetChatColors() {
    localStorage.removeItem('chatColors');
    // Reaplica as cores (que agora usarão os padrões do CSS)
     const root = document.documentElement;
     root.style.removeProperty('--chat-sent-bg');
     root.style.removeProperty('--chat-sent-text');
     root.style.removeProperty('--chat-received-bg');
     root.style.removeProperty('--chat-received-text');

     // Atualiza os inputs de cor para os padrões CSS
     const defaultSentBg = getComputedStyle(root).getPropertyValue('--chat-sent-bg').trim();
     const defaultSentText = getComputedStyle(root).getPropertyValue('--chat-sent-text').trim();
     const defaultReceivedBg = getComputedStyle(root).getPropertyValue('--chat-received-bg').trim();
     const defaultReceivedText = getComputedStyle(root).getPropertyValue('--chat-received-text').trim();

     const sentBgInput = document.getElementById('chatSentBg');
     const sentTextInput = document.getElementById('chatSentText');
     const receivedBgInput = document.getElementById('chatReceivedBg');
     const receivedTextInput = document.getElementById('chatReceivedText');

     if (sentBgInput) sentBgInput.value = defaultSentBg;
     if (sentTextInput) sentTextInput.value = defaultSentText;
     if (receivedBgInput) receivedBgInput.value = defaultReceivedBg;
     if (receivedTextInput) receivedTextInput.value = defaultReceivedText;
}
