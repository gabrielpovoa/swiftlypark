<?php $this->partial('head', ['title' => $title]); ?>

<section class="p-8 min-h-screen w-full font-sans bg-[#111827] text-white">

    <!-- Cabeçalho -->
    <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
        <h1 class="text-3xl font-bold text-[#FAFFFD]">Gerenciamento de Vagas</h1>
        <a href="/vacancy"
           class="inline-flex items-center px-4 py-2 rounded-lg bg-[#1F2937] hover:bg-[#374151] text-white font-semibold transition-colors duration-300 gap-2">
            <i data-lucide="arrow-left" class="w-5 h-5"></i>
            Voltar para Vagas
        </a>
    </div>

    <!-- Filtros -->
    <!-- Filtros Modernos -->
    <form method="GET" action="/vacancy/manage"
          class="bg-[#1F2937] p-6 rounded-2xl mb-8 flex flex-col md:flex-row justify-between items-center gap-4">

        <!-- Categoria com ícone -->
        <div class="flex items-center gap-2 w-full md:w-auto relative">
            <i data-lucide="grid" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
            <select name="categoria" id="filter-category"
                    class="bg-[#111827] text-white rounded-lg pl-10 pr-8 p-2 focus:outline-none focus:ring-2 focus:ring-[#3B82F6] w-full md:w-auto appearance-none">
                <option value="all" <?= ($filtros['categoria'] ?? '') === 'all' ? 'selected' : '' ?>>Todas</option>
                <option value="carro" <?= ($filtros['categoria'] ?? '') === 'carro' ? 'selected' : '' ?>>Carro</option>
                <option value="moto" <?= ($filtros['categoria'] ?? '') === 'moto' ? 'selected' : '' ?>>Moto</option>
                <option value="caminhao" <?= ($filtros['categoria'] ?? '') === 'caminhao' ? 'selected' : '' ?>>Caminhão</option>
                <option value="app" <?= ($filtros['categoria'] ?? '') === 'app' ? 'selected' : '' ?>>App</option>
            </select>
            <i data-lucide="chevron-down" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-300 pointer-events-none"></i>
        </div>

        <!-- Placa com ícone de busca -->
        <div class="flex items-center gap-2 w-full md:w-auto relative">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
            <input type="text" name="placa" id="search-plate" placeholder="Buscar por placa..."
                   value="<?= htmlspecialchars($filtros['placa'] ?? '') ?>"
                   class="bg-[#111827] text-white rounded-lg pl-10 p-2 focus:outline-none focus:ring-2 focus:ring-[#3B82F6] w-full md:w-auto transition-colors duration-300"/>
        </div>

        <!-- Botão de aplicar filtros com ícone -->
        <button type="submit"
                class="flex items-center justify-center gap-2 px-6 py-2 bg-[#453F78] hover:bg-[#2563EB] text-white font-semibold rounded-lg w-full md:w-auto transition-colors duration-300">
            <i data-lucide="filter" class="w-4 h-4"></i>
            Aplicar Filtros
        </button>
    </form>


    <!-- Lista de vagas -->
    <div id="vacancy-list" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-6">
        <?php if (!empty($vagas)): ?>

            <?php
            $mapaImagens = [
                'carro'    => 'car.png',
                'moto'     => 'moto.png',
                'caminhao' => 'truck.png',
                'app'      => 'uber.png'
            ];
            ?>

            <?php foreach ($vagas as $vaga): ?>
                <?php
                $categoria = $vaga['categoria'];
                $imagem = $mapaImagens[$categoria] ?? 'default.png';
                $bgColor = $vaga['status'] === 'livre' ? 'bg-[#33673B]' : 'bg-[#720E07]';
                ?>
                <div class="relative <?= $bgColor ?> rounded-2xl p-4 text-center transform transition-all duration-300 hover:scale-105">

                    <!-- Nome da vaga -->

                    <!-- Imagem -->
                    <img src="/images/<?= htmlspecialchars($imagem) ?>"
                         alt="<?= htmlspecialchars($categoria) ?>"
                         class="w-24 h-24 object-contain mb-2 mx-auto"/>

                    <!-- Placa -->
                    <h3 class="text-lg font-bold text-white <?= $vaga['status'] === 'livre' ? 'opacity-70' : '' ?>">
                        <?= $vaga['status'] === 'livre' ? 'Livre' : strtoupper($vaga['placa']) ?>
                    </h3>

                    <!-- Categoria -->
                    <p class="text-sm text-gray-200 <?= $vaga['status'] === 'livre' ? 'opacity-70' : '' ?>">
                        <?= ucfirst($categoria) ?>
                    </p>

                    <!-- Entrada e botão -->
                    <?php if ($vaga['status'] !== 'livre'): ?>
                        <p class="text-sm text-gray-200">Entrada: <?= date('H:i', strtotime($vaga['hora_entrada'])) ?></p>
                        <div class="mt-3">
                            <button class="finalizar bg-[#000000] hover:bg-[#14213D] text-white px-3 py-1 rounded-lg text-sm transition-colors duration-300"
                                    data-id="<?= $vaga['id_vaga'] ?>">
                                Finalizar
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

        <?php else: ?>
            <p class="col-span-full text-center text-gray-400 text-lg">Nenhuma vaga encontrada.</p>
        <?php endif; ?>
    </div>

</section>
