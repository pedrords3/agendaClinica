// Sistema de Tema Global
class ThemeManager {
    constructor() {
        this.currentTheme = localStorage.getItem('theme') || 'light';
        this.init();
    }

    init() {
        // Aplicar tema salvo em todas as páginas
        this.applyTheme(this.currentTheme);
        this.setupEventListeners();
        this.injectThemeToggle();
    }

    applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);
        this.updateThemeIcon(theme);
        
        // Disparar evento para componentes que precisam se adaptar
        window.dispatchEvent(new CustomEvent('themeChanged', { detail: theme }));
    }

    toggleTheme() {
        const newTheme = this.currentTheme === 'light' ? 'dark' : 'light';
        this.currentTheme = newTheme;
        this.applyTheme(newTheme);
    }

    updateThemeIcon(theme) {
        const themeToggle = document.getElementById('themeToggle');
        if (themeToggle) {
            const icon = themeToggle.querySelector('i');
            if (theme === 'dark') {
                icon.className = 'fas fa-sun';
                themeToggle.title = 'Modo Claro';
            } else {
                icon.className = 'fas fa-moon';
                themeToggle.title = 'Modo Escuro';
            }
        }
    }

    setupEventListeners() {
        const themeToggle = document.getElementById('themeToggle');
        if (themeToggle) {
            themeToggle.addEventListener('click', () => this.toggleTheme());
        }
    }

    injectThemeToggle() {
        // Garantir que o botão de tema existe em todas as páginas
        if (!document.getElementById('themeToggle')) {
            const themeSwitch = document.createElement('div');
            themeSwitch.className = 'theme-switch';
            themeSwitch.innerHTML = `
                <button class="btn-theme" id="themeToggle">
                    <i class="fas fa-moon"></i>
                </button>
            `;
            document.body.appendChild(themeSwitch);
            this.setupEventListeners();
        }
    }

    getCurrentTheme() {
        return this.currentTheme;
    }
}

// Inicializar quando o DOM estiver carregado
document.addEventListener('DOMContentLoaded', function() {
    window.themeManager = new ThemeManager();
});