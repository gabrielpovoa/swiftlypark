document.addEventListener("DOMContentLoaded", () => {
    const newPasswordInput = document.getElementById("new_password");
    const strengthBar = document.getElementById("password-strength");

    if (!newPasswordInput || !strengthBar) return;

    newPasswordInput.addEventListener("input", () => {
        const password = newPasswordInput.value;
        const strength = calculateStrength(password);

        updateStrengthBar(strength);
    });
});

/**
 * Retorna um valor de 0 a 100 baseado na força da senha
 */
function calculateStrength(password) {
    let score = 0;

    // Critérios
    const length = password.length >= 8;
    const hasUpper = /[A-Z]/.test(password);
    const hasLower = /[a-z]/.test(password);
    const hasNumber = /[0-9]/.test(password);
    const hasSymbol = /[^A-Za-z0-9]/.test(password);

    // Pontuação
    if (length) score += 25;
    if (hasUpper) score += 15;
    if (hasLower) score += 15;
    if (hasNumber) score += 20;
    if (hasSymbol) score += 25;

    return Math.min(score, 100);
}

/**
 * Atualiza visualmente a barra
 */
function updateStrengthBar(strength) {
    const bar = document.getElementById("password-strength");

    bar.style.width = strength + "%";

    if (strength < 30) {
        bar.style.backgroundColor = "#ef4444"; // Vermelho
    } else if (strength < 60) {
        bar.style.backgroundColor = "#f59e0b"; // Amarelo
    } else if (strength < 85) {
        bar.style.backgroundColor = "#3b82f6"; // Azul
    } else {
        bar.style.backgroundColor = "#22c55e"; // Verde forte
    }
}
