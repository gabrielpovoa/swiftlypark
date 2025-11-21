<?php $this->partial('head', ['title' => $title]); ?>

<section
    class="flex flex-col items-center justify-center min-h-screen bg-gradient-to-br from-blue-700/80 to-blue-900/90 px-4">

    <div class="w-full max-w-md bg-gradient-to-br from-blue-800/80 to-blue-900/95 shadow-xl rounded-2xl px-8 py-10">
        <h2 class="text-white text-3xl mb-8 font-bold text-center tracking-wide">Recuperar Senha</h2>

        <?php if (!empty($success)): ?>
            <p class="text-green-400 mb-6 text-center font-medium"><?= htmlspecialchars($success) ?></p>
        <?php elseif (!empty($error)): ?>
            <p class="text-red-400 mb-6 text-center font-medium"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form action="/login/recovery/send" method="POST" class="space-y-6">

            <div class="flex flex-col">
                <label for="email" class="text-white font-semibold mb-2">Email cadastrado</label>
                <input type="email" id="email" name="email" required
                    value="<?= isset($email) ? htmlspecialchars($email) : '' ?>"
                    class="w-full px-4 py-3 rounded-xl bg-blue-700/70 text-white placeholder-white focus:outline-none focus:ring-2 focus:ring-blue-400 transition-all duration-300"
                    placeholder="seu@email.com">
            </div>

            <?php if (!empty($showNewPassword)): ?>
                <div class="flex flex-col">
                    <label for="new_password" class="text-white font-semibold mb-2">Nova Senha</label>
                    <input type="password" id="new_password" name="new_password" required
                        class="w-full px-4 py-3 rounded-xl bg-blue-700/70 text-white placeholder-white focus:outline-none focus:ring-2 focus:ring-blue-400 transition-all duration-300"
                        placeholder="••••••••">
                </div>

                <div class="flex flex-col">
                    <label for="confirm_password" class="text-white font-semibold mb-2">Confirme a Nova Senha</label>
                    <input type="password" id="confirm_password" name="confirm_password" required
                        class="w-full px-4 py-3 rounded-xl bg-blue-700/70 text-white placeholder-white focus:outline-none focus:ring-2 focus:ring-blue-400 transition-all duration-300"
                        placeholder="••••••••">
                </div>
            <?php endif; ?>

            <button type="submit"
                class="w-full py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-500 hover:to-blue-600 rounded-2xl font-bold text-white shadow-lg hover:shadow-2xl transition-all duration-300">
                <?= !empty($showNewPassword) ? 'Atualizar Senha' : 'Enviar' ?>
            </button>

        </form>

    </div>

    <div class="flex flex-col gap-5 items-center justify-center mt-6">
        <p class="text-white text-md">
            Lembrou sua senha?
            <a href="/login"
                class="ml-2 px-4 py-2 bg-blue-600/80 hover:bg-blue-500/80 rounded-xl text-white font-semibold shadow-md hover:shadow-lg transition-all duration-300">
                Faça login
            </a>
        </p>
        <p class="text-white text-xs">
            Não possui uma conta?
            <a href="/CreateAcc" class=" py-2 text-white font-semibold">
                Crie uma!
            </a>
        </p>
    </div>

</section>

<?php $this->partial('footer'); ?>