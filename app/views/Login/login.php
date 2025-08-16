<?php $this->partial('head', ['title' => $title]); ?>

<section class="flex items-center justify-center h-screen bg-[#485696] px-4">

    <div class="w-full max-w-md bg-[#364574] shadow-md rounded px-8 py-10">

        <h2 class="text-white text-3xl mb-8 font-semibold text-center">Login</h2>

        <?php if (!empty($error)) : ?>
            <p class="text-red-400 mb-6 text-center"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form action="/login/authenticate" method="POST" class="space-y-6">

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
                Entrar
            </button>

        </form>

    </div>

</section>

<?php $this->partial('footer'); ?>
