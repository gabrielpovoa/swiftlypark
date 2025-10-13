<?php
$this->partial('head', ['title' => $title]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userName  = $_SESSION['user_name']  ?? 'Visitante';
$userEmail = $_SESSION['user_email'] ?? 'não informado';
?>

<section class="p-6 flex flex-col gap-6 min-h-screen w-full bg-gray-900 text-white">

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 max-w-6xl mx-auto">

        <!-- Perfil -->
        <aside class="bg-gradient-to-br from-blue-700/80 to-blue-900/80 rounded-xl shadow-lg p-8 flex flex-col items-center justify-center text-center hover:scale-[1.02] transition-transform duration-300">
            <div class="w-28 h-28 rounded-full bg-blue-600 flex items-center justify-center text-4xl font-bold shadow-md">
                <?= strtoupper(substr($userName, 0, 1)) ?>
            </div>
            <h2 class="mt-4 text-2xl font-semibold tracking-wide"><?= htmlspecialchars($userName) ?></h2>
            <hr class="w-3/4 my-6 border-blue-400/40">
            <p class="text-gray-300 text-sm"><?= htmlspecialchars($userEmail) ?></p>

        </aside>

        <!-- Formulário -->
        <div class="lg:col-span-2 bg-gradient-to-br from-slate-800/90 to-gray-900/90 rounded-xl shadow-lg p-8">
            <h2 class="text-3xl font-semibold text-center mb-8 border-b border-blue-500 pb-3 tracking-wide">
                ⚙️ Configurações da Conta
            </h2>

            <form action="/Profile/changePassword" method="POST" class="space-y-8">

                <!-- Alterar Senha -->
                <div class="bg-gradient-to-br from-blue-700/20 to-blue-900/20 p-6 rounded-lg shadow-md">
                    <h3 class="text-xl font-semibold mb-4">Alterar Senha</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="current_password" class="block font-medium mb-2 text-blue-300">Senha Atual</label>
                            <input type="password" id="current_password" name="current_password"
                                   class="w-full px-4 py-3 rounded-lg bg-[#1f2b4a] text-white placeholder-gray-400 border border-blue-500/40 focus:border-blue-400 focus:ring-2 focus:ring-blue-400 transition-all"
                                   placeholder="••••••••">
                        </div>
                        <div>
                            <label for="new_password" class="block font-medium mb-2 text-blue-300">Nova Senha</label>
                            <input type="password" id="new_password" name="new_password"
                                   class="w-full px-4 py-3 rounded-lg bg-[#1f2b4a] text-white placeholder-gray-400 border border-blue-500/40 focus:border-blue-400 focus:ring-2 focus:ring-blue-400 transition-all"
                                   placeholder="••••••••">
                        </div>
                    </div>

                    <!-- Barra de força -->
                    <div class="mt-5 h-2 w-full bg-gray-700 rounded-full overflow-hidden">
                        <div class="bg-green-500 w-1/3 h-full transition-all duration-500" id="password-strength"></div>
                    </div>
                </div>

                <!-- Botão -->
                <div class="flex justify-end">
                    <button type="submit"
                            class="px-8 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 rounded-lg font-semibold shadow-md transition-transform hover:scale-105 flex items-center gap-2">
                        <i data-lucide="save"></i> Salvar Alterações
                    </button>
                </div>

            </form>
        </div>

    </div>

</section>
