<section class="p-10 flex flex-col items-center w-full">

    <!-- Título -->
    <h1 class="text-4xl font-bold text-white mb-12 text-center">
        Vagas Disponíveis
    </h1>

    <!-- GRID RESPONSIVO -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 w-full max-w-6xl">

        <!-- CARD BASE -->
        <?php
        $items = [
            [
                'label' => 'CARROS',
                'count' => $counts['carro'] ?? 0,
                'type' => 'carro',
                'image' => '/images/car.png'
            ],
            [
                'label' => 'MOTOS',
                'count' => $counts['moto'] ?? 0,
                'type' => 'moto',
                'image' => '/images/moto.png'
            ],
            [
                'label' => 'CAMINHÃO',
                'count' => $counts['caminhao'] ?? 0,
                'type' => 'caminhao',
                'image' => '/images/truck.png'
            ],
            [
                'label' => 'APPS (Uber, 99Pop)',
                'count' => $counts['app'] ?? 0,
                'type' => 'app',
                'popular' => true,
                'image' => '/images/uber.png'
            ],
        ];

        foreach ($items as $item):
        ?>

            <a href="/vacancy/apply?type=<?= $item['type'] ?>"
               class="relative rounded-3xl bg-gradient-to-b from-[#1c2947] to-[#0f1625]
                      shadow-xl hover:shadow-2xl transition-all duration-300 
                      hover:scale-[1.03] p-6 flex flex-col items-center text-white">

                <!-- BADGE POPULAR -->
                <?php if (!empty($item['popular'])): ?>
                    <div class="absolute -top-3 right-3 bg-green-500 text-black font-bold text-xs px-3 py-1 rounded-full shadow-md">
                        POPULAR ★★★
                    </div>
                <?php endif; ?>

                <!-- IMAGEM -->
                <div class="mb-4 w-24 h-24 flex items-center justify-center">
                    <img src="<?= $item['image'] ?>"
                         alt="<?= $item['label'] ?>"
                         class="w-20 h-20 object-contain drop-shadow-lg opacity-90">
                </div>

                <!-- Título -->
                <h2 class="text-xl font-extrabold tracking-wide text-center">
                    <?= $item['label'] ?>
                </h2>

                <!-- Número -->
                <p class="text-6xl font-black mt-4 mb-1 drop-shadow-lg">
                    <?= $item['count'] ?>
                </p>

                <!-- Subtexto -->
                <p class="text-sm text-gray-300">vagas disponíveis</p>

                <!-- “Ano” -->
            </a>

        <?php endforeach; ?>
    </div>

    <!-- Botões -->
    <div class="flex flex-wrap justify-center gap-6 mt-14">

        <a href="vacancy/manage"
           class="flex items-center gap-2 px-6 py-3 rounded-xl bg-[#2B4570] 
                  hover:bg-white hover:text-[#2B4570] text-white font-semibold 
                  transition-all duration-500 shadow-lg hover:shadow-xl">
            <i data-lucide="layout-grid" class="w-5 h-5"></i> Gerenciar Vagas
        </a>

        <a href="/CreateVacancy"
           class="flex items-center gap-2 px-6 py-3 rounded-xl bg-[#2B4570] 
                  hover:bg-white hover:text-[#2B4570] text-white font-semibold 
                  transition-all duration-500 shadow-lg hover:shadow-xl">
            <i data-lucide="plus" class="w-5 h-5"></i> Adicionar Novas Vagas
        </a>

    </div>

</section>
