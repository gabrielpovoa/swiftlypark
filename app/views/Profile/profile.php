<?php $this->partial('head', ['title' => $title]); ?>

<section class="min-h-screen w-full flex items-center justify-center bg-[#485696] py-6 px-4">

    <div class="w-full h-full bg-[#364574] shadow-lg rounded-none px-8 py-10 text-white flex flex-col">

        <h2 class="text-3xl mb-8 font-semibold text-center">Meu Perfil</h2>

        <form action="/profile/update" method="POST" enctype="multipart/form-data" class="flex flex-col flex-grow space-y-6">

            <!-- Foto de perfil -->
            <div class="flex flex-col items-center mb-6">
                <div class="relative w-40 h-40 rounded-full overflow-hidden mb-4 border-4 border-blue-500">
                    <img src="https://via.placeholder.com/150" alt="Foto de Perfil" class="w-full h-full object-cover">
                    <label for="profile_picture" class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center cursor-pointer opacity-0 hover:opacity-100 transition-opacity">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.867-1.39A2 2 0 0110.45 4h3.1a2 2 0 011.664.89l.867 1.39a2 2 0 001.664.89H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </label>
                </div>
                <input type="file" id="profile_picture" name="profile_picture" class="hidden" accept="image/*">
            </div>

            <!-- Nome -->
            <div>
                <label for="username" class="block font-medium mb-2">Nome de Usuário</label>
                <input type="text" id="username" name="username" value="Nome do Usuário" required
                       class="w-full px-4 py-3 rounded-lg bg-[#485696] text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="Seu nome">
            </div>

            <!-- Email -->
            <div>
                <label for="email" class="block font-medium mb-2">E-mail</label>
                <input type="email" id="email" name="email" value="usuario@email.com" required
                       class="w-full px-4 py-3 rounded-lg bg-[#485696] text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="seu@email.com">
            </div>

            <hr class="border-gray-500 my-6">

            <!-- Alterar senha -->
            <div class="space-y-4">
                <h3 class="text-xl font-semibold">Alterar Senha</h3>
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

            <button type="submit"
                    class="w-full py-3 bg-blue-700 hover:bg-blue-800 rounded-lg font-semibold text-white transition-colors duration-300 mt-6">
                Salvar Alterações
            </button>

        </form>

    </div>

</section>
