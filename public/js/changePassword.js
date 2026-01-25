document.addEventListener("DOMContentLoaded", () => {
    const newPasswordInput = document.getElementById("new_password");
    const strengthBar = document.getElementById("password-strength");
    const strengthText = document.getElementById("password-strength-text");

    if (!newPasswordInput || !strengthBar || !strengthText) return;

    newPasswordInput.addEventListener("input", () => {
        const password = newPasswordInput.value;

        if (password.length === 0) {
            strengthBar.style.width = "0%";
            strengthText.innerHTML = 'Força da senha: <span class="font-semibold text-white">—</span>';
            return;
        }

        const strength = calculateStrength(password);
        updateStrengthBar(strength);
    });
});

/**
 * Retorna um valor de 0 a 100 baseado na força da senha
 */
document.addEventListener("DOMContentLoaded", () => {
    const newPasswordInput = document.getElementById("new_password");
    const strengthBar = document.getElementById("password-strength");
    const strengthText = document.getElementById("password-strength-text");

    if (!newPasswordInput || !strengthBar || !strengthText) return;

    newPasswordInput.addEventListener("input", () => {
        const password = newPasswordInput.value;

        // Se o campo estiver vazio, volta ao estado inicial
        if (password.length === 0) {
            strengthBar.style.width = "0%";
            strengthText.innerText = "—";
            strengthText.className = "text-xs font-bold uppercase tracking-widest text-slate-500";
            return;
        }

        const strength = calculateStrength(password);
        updateUI(strength, strengthBar, strengthText);
    });
});

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

    // Lógica de feedback dinâmico
    if (strength < 40) {
        bar.style.backgroundColor = "#ef4444"; // Vermelho
        text.innerText = "Fraca";
        text.className = "text-xs font-bold uppercase tracking-widest text-red-400";
    }
    else if (strength < 85) {
        bar.style.backgroundColor = "#f59e0b"; // Amarelo
        text.innerText = "Está bom";
        text.className = "text-xs font-bold uppercase tracking-widest text-yellow-400";
    }
    else {
        bar.style.backgroundColor = "#22c55e"; // Verde
        text.innerText = "Forte, anote-a ou salve para não esquecer";
        text.className = "text-xs font-bold uppercase tracking-widest text-emerald-400";
    }
}
/**
 * Atualiza visualmente a barra e o texto informativo
 */
function updateStrengthBar(strength) {
    const bar = document.getElementById("password-strength");
    const textIndicator = document.getElementById("password-strength-text");

    bar.style.width = strength + "%";

    if (strength < 40) {
        // FRACA
        bar.style.backgroundColor = "#ef4444"; // Vermelho
        textIndicator.innerHTML = '<span class="text-red-400 font-bold">Fraca</span>';
    } else if (strength < 85) {
        // MEDIANA
        bar.style.backgroundColor = "#f59e0b"; // Amarelo/Laranja
        textIndicator.innerHTML = '<span class="text-yellow-400 font-bold">Está bom</span>';
    } else {
        // FORTE
        bar.style.backgroundColor = "#22c55e"; // Verde
        textIndicator.innerHTML = '<span class="text-emerald-400 font-bold">Forte, anote-a ou salve para não esquecer</span>';
    }
}