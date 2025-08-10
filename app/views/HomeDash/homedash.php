<?php $this->partial('head', ['title' => $title]); ?>

<section class="p-6 flex flex-col justify-between min-h-screen w-full">

    <div class="grid grid-cols-[12rem_12rem_12rem_minmax(30%,1fr)] gap-x-3 gap-y-6">

        <div class="flex items-center justify-center rounded bg-[#2B4570]/45 shadow-md overflow-hidden w-48 h-48">
            <img src="/images/moto.png" alt="Moto" class="w-full h-full object-contain"/>
        </div>

        <div class="flex items-center justify-center rounded bg-[#2B4570]/45 shadow-md overflow-hidden w-48 h-48">
            <img src="/images/car.png" alt="Carro" class="w-full h-full object-contain"/>
        </div>

        <div class=" mb-8 flex items-center justify-center rounded bg-[#2B4570]/45 shadow-md overflow-hidden w-48 h-48">
            <img src="/images/truck.png" alt="Caminhão" class="w-full h-full object-contain"/>
        </div>

        <div class="bg-[#2B4570]/80 text-white font-mono rounded-md shadow-md p-4 mr-4 h-48 overflow-y-auto">
            <h2 class="text-lg font-semibold mb-2 border-b border-blue-400 pb-1">Registro de Entradas/Saídas</h2>

            <ul class="space-y-1 text-sm">
                <?php foreach ($logEntry as $log): ?>
                    <li>📥 Placa <strong><?= htmlspecialchars($log['placa']) ?></strong> acabou de chegar às <?= htmlspecialchars($log['hora_entrada']) ?></li>
                <?php endforeach; ?>
            </ul>

        </div>


        <div class="flex items-center justify-center rounded bg-[#2B4570]/45 shadow-md overflow-hidden w-48 h-48">
            <img src="/images/uber.png" alt="Uber" class="w-full h-full object-cover"/>
        </div>

    </div>

    <div class="mb-8 flex justify-between items-start ">
        <div class="flex-1">
            <h1 class="text-white text-2xl font-bold uppercase">Faturamento - dia</h1>
            <span class="text-white text-lg">
            R$
        <?php
            if (!empty($dailyIncome)) {
                // Soma todos os valores do array
                $total = array_sum($dailyIncome);
                echo number_format($total, 2, ',', '.');
            } else {
                echo '0,00'; // Caso não tenha transações
            }
        ?>
            </span>
        </div>

        <a href="/vacancy"
           class="bg-[#2B4570] text-white px-4 py-2 rounded hover:text-[#2B4570] hover:bg-white transition-colors duration-500 font-semibold">
            Ver Vagas
        </a>
    </div>

</section>
