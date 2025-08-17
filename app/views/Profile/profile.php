<?php $this->partial('head', ['title' => $title]);


    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $userName = $_SESSION['user_name'] ?? 'Visitante';
    $userEmail = $_SESSION['user_email'];


?>

<section class="min-h-screen w-full flex items-center justify-center py-6 px-4">

    <div class="w-full h-full bg-[#364574] shadow-lg rounded-md px-8 py-10 text-white flex flex-col">

        <h2 class="text-3xl mb-8 font-semibold text-center">Meu Perfil</h2>

        <form action="/profile/update" method="POST" class="flex flex-col flex-grow space-y-6">

           <!-- Nome -->
           <div class="grid grid-cols-2 gap-x-6">
               <div>
                   <label for="username" class="block font-medium mb-2">Nome de Usuário</label>
                   <input type="text" id="username" name="username" value="<?= htmlspecialchars($userName) ?>" disabled
                          class="w-full px-4 py-3 rounded-lg bg-[#485696] text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
               </div>

               <!-- Email -->
               <div>
                   <label for="email" class="block font-medium mb-2">E-mail</label>
                   <input type="email" id="email" name="email" value="<?= htmlspecialchars($userEmail) ?>" disabled
                          class="w-full px-4 py-3 rounded-lg bg-[#485696] text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
               </div>
           </div>

            <hr class="border-gray-500 my-6">

            <!-- Alterar senha -->
            <div class="space-y-4">
                <h3 class="text-xl font-semibold">Alterar Senha</h3>
                <div class="grid grid-cols-2 gap-x-6">
                    <div>
                        <label for="current_password" class="block font-medium mb-2">Senha Atual</label>
                        <input type="password" id="current_password" name="current_password"
                               class="w-full px-4 py-3 rounded-lg bg-[#485696] text-white placeholder-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="••••••••">
                    </div>
                    <div>
                        <label for="new_password" class="block font-medium mb-2">Nova Senha</label>
                        <input type="password" id="new_password" name="new_password"
                               class="w-full px-4 py-3 rounded-lg bg-[#485696] text-white placeholder-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="••••••••">
                    </div>
                </div>
            </div>

            <button type="submit"
                    class="cursor-pointer w-full py-3 bg-blue-700 hover:bg-blue-800 rounded-lg font-semibold text-white transition-colors duration-300 mt-6">
                Salvar Alterações
            </button>

        </form>

    </div>

</section>
