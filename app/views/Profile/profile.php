<?php
    $this->partial('head', ['title' => $title]);

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $userName  = $_SESSION['user_name']  ?? 'Visitante';
    $userEmail = $_SESSION['user_email'] ?? 'não informado';
?>

<section class="min-h-screen w-full flex items-center justify-center py-6 px-4">

    <div class="w-full max-w-5xl flex flex-col lg:flex-row gap-8">

        <!-- Profile Card -->
        <aside class="w-full lg:w-1/3 bg-[#364574] shadow-lg rounded-xl p-8 flex flex-col items-center text-white">
            <!-- Avatar with initials -->
            <div class="w-24 h-24 rounded-full bg-blue-600 flex items-center justify-center text-3xl font-bold shadow-md">
                <?= strtoupper(substr($userName, 0, 1)) ?>
            </div>
            <h2 class="mt-4 text-2xl font-semibold"><?= htmlspecialchars($userName) ?></h2>
            <p class="text-gray-300"><?= htmlspecialchars($userEmail) ?></p>
        </aside>

        <!-- Profile Form -->
        <div class="flex-1 bg-[#1F2937] shadow-lg rounded-xl px-8 py-10 text-white">
            <h2 class="text-3xl mb-8 font-semibold text-center">Configurações</h2>

            <form action="/Profile/changePassword" method="POST" class="flex flex-col space-y-8">

                <!-- Alterar Senha -->
                <div class="bg-[#374151] p-6 rounded-lg shadow-md space-y-4">
                    <h3 class="text-xl font-semibold">Alterar Senha</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="current_password" class="block font-medium mb-2">Senha Atual</label>
                            <input type="password" id="current_password" name="current_password"
                                   class="w-full px-4 py-3 rounded-lg bg-[#485696] text-white placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="••••••••">
                        </div>
                        <div>
                            <label for="new_password" class="block font-medium mb-2">Nova Senha</label>
                            <input type="password" id="new_password" name="new_password"
                                   class="w-full px-4 py-3 rounded-lg bg-[#485696] text-white placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="••••••••">
                        </div>
                    </div>

                    <!-- Password strength bar -->
                    <div class="mt-4 h-2 w-full bg-gray-600 rounded-full overflow-hidden">
                        <div class="bg-green-500 w-1/3 h-full transition-all" id="password-strength"></div>
                    </div>
                </div>

                <!-- Save Button -->
                <div class="flex justify-end">
                    <button type="submit"
                            class="px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 rounded-lg font-semibold shadow-md transition-transform hover:scale-105">
                        💾 Salvar Alterações
                    </button>
                </div>

            </form>
        </div>

    </div>

</section>
