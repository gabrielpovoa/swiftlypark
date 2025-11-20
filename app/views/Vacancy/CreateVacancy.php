<?php $this->partial('head', ['title' => $title]); ?>

<section class="p-8 flex items-center justify-center min-h-screen bg-gradient-to-br from-[#131b2e] to-[#0d111b]">

    <div class="w-full max-w-xl bg-[#1b2438] shadow-2xl rounded-[2rem] p-10 border border-white/10 backdrop-blur-sm">

        <!-- Cabeçalho -->
        <div class="text-center mb-10">
            <h1 class="text-4xl font-extrabold text-white tracking-wide">
                <?= htmlspecialchars($title) ?>
            </h1>
            <p class="text-gray-300 mt-2 text-sm">
                Crie vagas de forma rápida e organizada
            </p>
        </div>

        <!-- Mensagem de erro -->
        <?php if (!empty($errorMessage)): ?>
            <div class="bg-red-500/20 border border-red-400/40 text-red-300 px-4 py-3 rounded-xl mb-6 text-sm">
                <?= htmlspecialchars($errorMessage) ?>
            </div>
        <?php endif; ?>

        <!-- Formulário -->
        <form action="/CreateVacancy/store" method="POST" class="space-y-6">

            <!-- Categoria -->
            <div class="relative">
                <i data-lucide="layers" class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 w-5 h-5"></i>

                <select id="category" name="category" required
                        class="w-full pl-12 pr-10 py-4 rounded-2xl bg-[#26314d] text-white text-sm
                               focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all
                               appearance-none">
                    <option value="">Selecione uma categoria</option>
                    <option value="carro">Carro</option>
                    <option value="moto">Moto</option>
                    <option value="caminhao">Caminhão</option>
                    <option value="app">App (Uber/99)</option>
                </select>

                <div class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2">
                    <i data-lucide="chevron-down" class="w-5 h-5 text-gray-400"></i>
                </div>
            </div>

            <!-- Quantidade -->
            <div class="relative">
                <i data-lucide="hash" class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 w-5 h-5"></i>

                <input type="number" id="amount" name="amount" min="1" required
                       class="w-full pl-12 pr-4 py-4 rounded-2xl bg-[#26314d] text-white placeholder-gray-400 text-sm
                              focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                       placeholder="Ex: 10">
            </div>

            <!-- Botão -->
            <button type="submit"
                    class="w-full py-4 rounded-2xl font-semibold text-white bg-gradient-to-r from-blue-600 to-blue-700
                           hover:from-blue-700 hover:to-blue-800 transition-all shadow-lg hover:shadow-blue-900/40
                           flex items-center justify-center gap-2 text-sm">
                <i data-lucide="plus" class="w-5 h-5"></i>
                Criar Vagas
            </button>
        </form>

        <!-- Mensagem de sucesso -->
        <?php if (!empty($successMessage)): ?>
            <div class="mt-6 bg-green-500/20 border border-green-400/40 text-green-300 px-4 py-3 rounded-xl text-sm">
                <?= htmlspecialchars($successMessage) ?>
            </div>
        <?php endif; ?>

        <!-- Link de navegação -->
        <div class="mt-8 text-center">
            <a href="/" class="text-blue-400 hover:text-blue-300 hover:underline text-sm tracking-wide">
                ← Voltar ao Dashboard
            </a>
        </div>

    </div>
</section>
