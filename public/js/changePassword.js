document.addEventListener("DOMContentLoaded", () => {
    const form = document.querySelector('form[action="/Profile/changePassword"]');
    const currentPasswordInput = document.getElementById("current_password");
    const newPasswordInput = document.getElementById("new_password");
    const strengthBar = document.getElementById("password-strength");
    const strengthText = document.getElementById("password-strength-text");

    if (!newPasswordInput || !strengthBar || !strengthText) return;

    // --- 1. Lógica do Input (Força e Identidade) ---
    newPasswordInput.addEventListener("input", () => {
        const password = newPasswordInput.value;
        const currentVal = currentPasswordInput.value;

        // Caso 1: Vazio
        if (password.length === 0) {
            resetUI(strengthBar, strengthText);
            return;
        }

        // Caso 2: Senha Idêntica
        if (password === currentVal && password !== "") {
            // Deixa a barra opaca e com cor de alerta (rose/vermelho suave)
            strengthBar.style.width = "100%";
            strengthBar.style.backgroundColor = "rgba(244, 63, 94, 0.3)"; // Rose opaco
            strengthText.innerText = "A nova senha não pode ser igual à atual";
            strengthText.className = "text-xs font-bold uppercase tracking-widest text-rose-500/60";
            return;
        }

        // Caso 3: Cálculo Normal
        const strength = calculateStrength(password);
        updateUI(strength, strengthBar, strengthText);
    });

    // --- 2. Validação de Submit ---
    if (form) {
        form.addEventListener("submit", (e) => {
            if (currentPasswordInput.value === newPasswordInput.value && newPasswordInput.value !== "") {
                e.preventDefault();
                showErrorNotification(newPasswordInput);
            }
        });
    }
});

/**
 * Reseta a barra para o estado inicial
 */
function resetUI(bar, text) {
    bar.style.width = "0%";
    text.innerText = "—";
    text.className = "text-xs font-bold uppercase tracking-widest text-slate-500";
}

/**
 * Exibe a notificação flutuante de erro
 */
function showErrorNotification(input) {
    const existingError = document.getElementById('password-match-error');
    if (existingError) existingError.remove();

    const errorMsg = document.createElement('div');
    errorMsg.id = 'password-match-error';
    errorMsg.className = 'absolute -top-6 right-0 flex items-center gap-1.5 text-[9px] font-black uppercase tracking-widest text-rose-500 animate-in fade-in zoom-in duration-300';
    errorMsg.innerHTML = `<i data-lucide="alert-circle" class="w-3 h-3"></i><span>Senha idêntica à atual</span>`;

    input.parentElement.appendChild(errorMsg);
    if (window.lucide) lucide.createIcons();

    input.focus();
    input.classList.add('border-rose-500/50', 'shadow-[0_0_15px_rgba(244,63,94,0.1)]');

    setTimeout(() => {
        input.classList.remove('border-rose-500/50', 'shadow-[0_0_15px_rgba(244,63,94,0.1)]');
        const msg = document.getElementById('password-match-error');
        if (msg) msg.remove();
    }, 4000);
}

function calculateStrength(password) {
    let score = 0;
    if (password.length >= 8) score += 25;
    if (/[A-Z]/.test(password)) score += 15;
    if (/[a-z]/.test(password)) score += 15;
    if (/[0-9]/.test(password)) score += 20;
    if (/[^A-Za-z0-9]/.test(password)) score += 25;
    return Math.min(score, 100);
}

function updateUI(strength, bar, text) {
    bar.style.width = strength + "%";
    bar.style.opacity = "1"; // Garante que a opacidade volte ao normal

    if (strength < 40) {
        bar.style.backgroundColor = "#ef4444";
        text.innerText = "Fraca";
        text.className = "text-xs font-bold uppercase tracking-widest text-red-400";
    }
    else if (strength < 85) {
        bar.style.backgroundColor = "#f59e0b";
        text.innerText = "Está bom";
        text.className = "text-xs font-bold uppercase tracking-widest text-yellow-400";
    }
    else {
        bar.style.backgroundColor = "#22c55e";
        text.innerText = "Forte";
        text.className = "text-xs font-bold uppercase tracking-widest text-emerald-400";
    }
}