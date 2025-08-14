<?php $this->partial('head', ['title' => $title]); ?>

<section class="p-8 min-h-screen w-full rounded-md shadow-md text-white font-sans bg-[#111827]">

    <!-- Cabeçalho -->
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold">Gerenciamento de Vagas</h1>
        <a href="/vacancy"
           class="inline-flex items-center px-4 py-2 rounded-lg bg-gray-700 text-white font-semibold hover:bg-gray-600 transition-colors duration-300 shadow-md gap-2">
            <i data-lucide="arrow-left" class="w-5 h-5"></i>
            Voltar para Vagas
        </a>
    </div>

    <!-- Filtros -->
    <form method="GET" action="/vacancy/manage"
          class="bg-[#1F2937] p-6 rounded-lg shadow-xl mb-8 flex flex-col md:flex-row justify-between items-center gap-6">

        <!-- Categoria -->
        <div class="flex items-center gap-4 w-full md:w-auto">
            <label for="filter-category" class="text-gray-300 whitespace-nowrap">Categoria:</label>
            <select name="categoria" id="filter-category"
                    class="bg-gray-700 text-white rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-blue-500 w-full md:w-auto">
                <option value="all" <?= ($filtros['categoria'] ?? '') === 'all' ? 'selected' : '' ?>>Todas</option>
                <option value="carro" <?= ($filtros['categoria'] ?? '') === 'carro' ? 'selected' : '' ?>>Carro</option>
                <option value="moto" <?= ($filtros['categoria'] ?? '') === 'moto' ? 'selected' : '' ?>>Moto</option>
                <option value="caminhao" <?= ($filtros['categoria'] ?? '') === 'caminhao' ? 'selected' : '' ?>>Caminhão</option>
                <option value="app" <?= ($filtros['categoria'] ?? '') === 'app' ? 'selected' : '' ?>>App</option>
            </select>
        </div>

        <!-- Placa -->
        <div class="flex items-center gap-4 w-full md:w-auto">
            <label for="search-plate" class="text-gray-300 whitespace-nowrap">Placa:</label>
            <input type="text" name="placa" id="search-plate" placeholder="Buscar por placa..."
                   value="<?= htmlspecialchars($filtros['placa'] ?? '') ?>"
                   class="bg-gray-700 text-white rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-blue-500 w-full md:w-auto"/>
        </div>

        <!-- Botão -->
        <button type="submit"
                class="px-6 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-colors duration-300 w-full md:w-auto">
            Aplicar Filtros
        </button>
    </form>

    <!-- Lista de vagas -->
    <div id="vacancy-list" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-6">
        <?php if (!empty($vagas)): ?>

            <?php
            // Mapa de imagens para cada categoria
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
                ?>
                <div class="relative <?= $vaga['status'] === 'livre' ? 'bg-green-600' : 'bg-red-800' ?> rounded-lg shadow-md p-4 text-center transform transition-all duration-300 hover:scale-105 hover:shadow-2xl">

                    <!-- Nome da vaga -->
                    <div class="absolute top-2 left-2 text-sm text-gray-200 font-medium">
                        Vaga <?= htmlspecialchars((string) $vaga['id_vaga']) ?>
                    </div>

                    <!-- Imagem -->
                    <img src="/images/<?= htmlspecialchars($imagem) ?>"
                         alt="<?= htmlspecialchars($categoria) ?>"
                         class="w-20 h-20 object-contain mb-2 mx-auto <?= $vaga['status'] === 'livre' ? 'opacity-30' : '' ?>"/>

                    <!-- Placa -->
                    <h3 class="text-lg font-bold <?= $vaga['status'] === 'livre' ? 'opacity-30' : '' ?>">
                        <?= $vaga['status'] === 'livre' ? 'Livre' : strtoupper($vaga['placa']) ?>
                    </h3>

                    <!-- Categoria -->
                    <p class="text-sm text-gray-200 <?= $vaga['status'] === 'livre' ? 'opacity-30' : '' ?>">
                        <?= ucfirst($categoria) ?>
                    </p>

                    <!-- Entrada e botão -->
                    <?php if ($vaga['status'] !== 'livre'): ?>
                        <p class="text-sm text-gray-200">Entrada: <?= date('H:i', strtotime($vaga['hora_entrada'])) ?></p>
                        <div class="mt-4">
                            <button class="bg-gray-900 text-white px-3 py-1 rounded text-sm hover:bg-gray-800">
                                Finalizar
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

        <?php else: ?>
            <p class="col-span-full text-center text-gray-400">Nenhuma vaga encontrada.</p>
        <?php endif; ?>
    </div>

</section>
