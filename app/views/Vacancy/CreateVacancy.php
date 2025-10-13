<?php $this->partial('head', ['title' => $title]); ?>

<section class="p-8 flex items-center justify-center min-h-screen bg-gradient-to-br from-[#1e2a47] to-[#121826]">

    <div class="w-full max-w-lg bg-[#1f2b4a] shadow-lg rounded-2xl p-8">

        <!-- Cabeçalho -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-white"><?= htmlspecialchars($title) ?></h1>
            <p class="text-gray-300 mt-2">Adicione novas vagas ao sistema rapidamente</p>
        </div>

        <!-- Mensagem de erro -->
        <?php if (!empty($errorMessage)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6" role="alert">
                <?= htmlspecialchars($errorMessage) ?>
            </div>
        <?php endif; ?>

        <!-- Formulário -->
        <form action="/CreateVacancy/store" method="POST" class="space-y-6">

            <!-- Categoria -->
            <div class="relative">
                <i data-lucide="layers" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-300"></i>

                <select id="category" name="category" required
                        class="w-full pl-10 pr-10 py-3 rounded-lg bg-[#2d3a5c] text-white placeholder-gray-300
                   focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all
                   appearance-none">
                    <option value="">Selecione uma categoria</option>
                    <option value="carro">Carro</option>
                    <option value="moto">Moto</option>
                    <option value="caminhao">Caminhão</option>
                    <option value="app">App (Uber/99)</option>
                </select>

                <!-- Arrow customizado -->
                <div class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2">
                    <i data-lucide="chevron-down" class="w-4 h-4 text-gray-300"></i>
                </div>
            </div>


            <!-- Quantidade -->
            <div class="relative">
                <i data-lucide="hash" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-300"></i>
                <input type="number" id="amount" name="amount" min="1" required
                       class="w-full pl-10 pr-4 py-3 rounded-lg bg-[#2d3a5c] text-white placeholder-gray-300
                              focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                       placeholder="Ex: 10">
            </div>

            <!-- Botão -->
            <button type="submit"
                    class="w-full py-3 bg-blue-600 hover:bg-blue-700 rounded-lg font-semibold text-white flex items-center justify-center gap-2 transition-colors duration-300">
                <i data-lucide="plus"></i> Criar Vagas
            </button>
        </form>

        <!-- Mensagem de sucesso -->
        <?php if (!empty($successMessage)): ?>
            <div class="mt-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded" role="alert">
                <?= htmlspecialchars($successMessage) ?>
            </div>
        <?php endif; ?>

        <!-- Link de navegação -->
        <div class="mt-6 text-center">
            <a href="/" class="text-blue-400 hover:underline font-medium">
                Voltar ao Dashboard
            </a>
        </div>

    </div>
</section>
