<?php
$this->partial('head', ['title' => $title]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userName = $user['nome'] ?? 'Visitante';
$userEmail = $user['email'] ?? 'não informado';
$userPhoto = $user['photo'] ?? null;
?>

<section class="min-h-screen w-full bg-gradient-to-b from-gray-950 to-gray-900 text-white p-8">

    <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-10">

        <!-- Perfil -->
        <aside class="rounded-2xl p-10 backdrop-blur-xl bg-white/5 border border-white/10 shadow-xl flex flex-col items-center text-center">

            <!-- Foto do usuário -->
            <div class="relative group">

                <?php if (!empty($userPhoto)): ?>
                    <img src="/uploads/<?= htmlspecialchars($userPhoto) ?>"
                         alt="Foto do usuário"
                         class="w-32 h-32 rounded-full object-cover shadow-[0_0_20px_rgba(0,150,255,0.5)] border border-blue-500/30">
                <?php else: ?>
                    <div class="w-32 h-32 rounded-full bg-gradient-to-br from-blue-600 to-indigo-700 
                                flex items-center justify-center text-5xl font-bold 
                                shadow-[0_0_20px_rgba(0,150,255,0.5)]">
                        <?= strtoupper(substr($userName, 0, 1)) ?>
                    </div>
                <?php endif; ?>

                <!-- Upload de nova foto -->
                <form action="/Profile/uploadPhoto" method="POST" enctype="multipart/form-data"
                      class="absolute bottom-1 right-1 hidden group-hover:flex">
                    <label for="photo"
                           class="cursor-pointer bg-blue-600 hover:bg-blue-700 p-2 rounded-full shadow-lg">
                        <i data-lucide="camera" class="w-4 h-4 text-white"></i>
                        <input type="file" id="photo" name="photo" class="hidden"
                               accept="image/*" onchange="this.form.submit()">
                    </label>
                </form>
            </div>

            <h2 class="mt-6 text-3xl font-semibold bg-gradient-to-r from-blue-400 to-indigo-400 bg-clip-text text-transparent tracking-wide">
                <?= htmlspecialchars($userName) ?>
            </h2>

            <p class="text-gray-300 text-sm mt-2"><?= htmlspecialchars($userEmail) ?></p>

            <hr class="w-3/4 my-8 border-white/10">
        </aside>

        <!-- Formulário de alteração -->
        <div class="lg:col-span-2 rounded-2xl p-10 bg-white/5 backdrop-blur-xl border border-white/10 shadow-xl">

            <h2 class="text-4xl font-semibold text-center mb-10 tracking-wide bg-gradient-to-r from-blue-400 to-indigo-400 bg-clip-text text-transparent">
                ⚙️ Configurações da Conta
            </h2>

            <form action="/Profile/changePassword" method="POST" class="space-y-10">

                <!-- Alteração da senha -->
                <div class="p-8 rounded-xl bg-gradient-to-br from-blue-800/20 to-indigo-900/20 border border-blue-500/10 shadow-inner">

                    <h3 class="text-2xl font-semibold mb-6 tracking-wide text-blue-300">Alterar Senha</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <label for="current_password" class="block mb-2 text-blue-300 font-medium">
                                Senha Atual
                            </label>

                            <input type="password" id="current_password" name="current_password"
                                   class="w-full px-5 py-3 rounded-xl bg-gray-800/60 border border-blue-500/20 text-white 
                                          focus:border-blue-400 focus:ring-2 focus:ring-blue-500 transition-all 
                                          placeholder-gray-400"
                                   placeholder="••••••••">
                        </div>

                        <div>
                            <label for="new_password" class="block mb-2 text-blue-300 font-medium">
                                Nova Senha
                            </label>

                            <input type="password" id="new_password" name="new_password"
                                   class="w-full px-5 py-3 rounded-xl bg-gray-800/60 border border-blue-500/20 text-white 
                                          focus:border-blue-400 focus:ring-2 focus:ring-blue-500 transition-all 
                                          placeholder-gray-400"
                                   placeholder="••••••••">
                        </div>
                    </div>

                    <!-- Indicador de força da senha -->
                    <div class="mt-8">
                        <p id="password-strength-text" class="text-sm text-gray-300 mb-2">
                            Força da senha: <span class="font-semibold text-white">—</span>
                        </p>

                        <div class="h-2 w-full bg-gray-800 rounded-full overflow-hidden">
                            <div id="password-strength"
                                 class="w-0 h-full bg-red-500 rounded-full transition-all duration-500">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botão de salvar -->
                <div class="flex justify-end">
                    <button type="submit"
                            class="px-10 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 
                                   hover:from-blue-700 hover:to-indigo-700 rounded-xl font-semibold 
                                   shadow-xl hover:scale-105 transition-all flex items-center gap-2">
                        <i data-lucide="save"></i>
                        Salvar Alterações
                    </button>
                </div>

            </form>
        </div>

    </div>

</section>
