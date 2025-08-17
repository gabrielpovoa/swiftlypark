<?php $this->partial('head', ['title' => $title]); ?>

    <section class="flex flex-col items-center justify-center h-screen bg-[#485696] px-4">

        <div class="w-full max-w-xl bg-[#364574] shadow-md rounded px-8 py-6">

            <h2 class="text-white text-3xl mb-8 font-semibold text-center">Criar Conta</h2>

            <?php if (!empty($error)) : ?>
                <p class="text-red-400 mb-6 text-center"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <form action="/CreateAcc/create" method="POST" class="space-y-6">

                <div>
                    <label for="name" class="block text-white font-medium mb-2">Nome</label>
                    <input type="text" id="name" name="name" required
                           class="w-full px-4 py-3 rounded bg-[#485696] text-white placeholder-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="Seu nome completo">
                </div>

                <div>
                    <label for="email" class="block text-white font-medium mb-2">Email</label>
                    <input type="email" id="email" name="email" required
                           class="w-full px-4 py-3 rounded bg-[#485696] text-white placeholder-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="seu@email.com">
                </div>

                <div>
                    <label for="password" class="block text-white font-medium mb-2">Senha</label>
                    <input type="password" id="password" name="password" required
                           class="w-full px-4 py-3 rounded bg-[#485696] text-white placeholder-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="••••••••">
                </div>

                <button type="submit"
                        class="w-full py-3 bg-blue-700 hover:bg-blue-800 rounded font-semibold text-white transition-colors duration-300">
                    Criar Conta
                </button>

            </form>

        </div>
        <div class="flex items-center justify-center mt-6">
            <p class="text-white text-sm">Já possui uma Conta? <a href="login" class="hover:bg-[#008BF8] p-3 text-white transition-colors durantion-400 rounded-md">Acesse aqui!</a></p>
        </div>
    </section>

<?php $this->partial('footer'); ?>