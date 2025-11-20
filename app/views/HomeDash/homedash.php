<?php $this->partial('head', ['title' => $title]); ?>

<section class="p-6 flex flex-col gap-6 min-h-screen w-full bg-gray-900">

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">

        <!-- Coluna Veículos -->
        <div class="flex flex-col gap-4 lg:col-span-1">

            <!-- Moto -->
            <a href="/vacancy/apply?type=moto"
               class="flex flex-col items-center justify-center bg-gradient-to-br from-blue-700/80 to-blue-900/80
                      rounded-xl shadow-lg p-4 hover:scale-105 transition-transform duration-300 cursor-pointer">
                <img src="/images/moto.png" alt="Moto" class="w-28 h-28 object-contain mb-2"/>
                <span class="text-white font-semibold">Moto</span>
            </a>

            <!-- Carro -->
            <a href="/vacancy/apply?type=carro"
               class="flex flex-col items-center justify-center bg-gradient-to-br from-blue-700/80 to-blue-900/80
                      rounded-xl shadow-lg p-4 hover:scale-105 transition-transform duration-300 cursor-pointer">
                <img src="/images/car.png" alt="Carro" class="w-28 h-28 object-contain mb-2"/>
                <span class="text-white font-semibold">Carro</span>
            </a>

            <!-- Caminhão + App/Uber lado a lado -->
            <div class="flex gap-4">

                <!-- Caminhão -->
                <a href="/vacancy/apply?type=caminhao"
                   class="flex flex-col items-center justify-center bg-gradient-to-br from-blue-700/80 to-blue-900/80
                          rounded-xl shadow-lg p-4 hover:scale-105 transition-transform duration-300 flex-1 cursor-pointer">
                    <img src="/images/truck.png" alt="Caminhão" class="w-28 h-28 object-contain mb-2"/>
                    <span class="text-white font-semibold">Caminhão</span>
                </a>

                <!-- App/Uber -->
                <a href="/vacancy/apply?type=app"
                   class="flex flex-col items-center justify-center bg-gradient-to-br from-blue-700/80 to-blue-900/80
                          rounded-xl shadow-lg p-4 hover:scale-105 transition-transform duration-300 flex-1 cursor-pointer">
                    <img src="/images/uber.png" alt="App/Uber" class="w-28 h-28 object-contain mb-2"/>
                    <span class="text-white font-semibold">App/Uber</span>
                </a>

            </div>
        </div>

        <!-- Log -->
        <div class="h-96 lg:col-span-3 bg-gradient-to-br from-blue-700/80 to-blue-900/80 
             text-white rounded-xl shadow-lg p-6 flex flex-col">
             
            <div class="flex justify-between items-center border-b border-blue-400 pb-2 mb-4">
                <h2 class="text-xl font-semibold tracking-wide">Log de Entradas/Saídas</h2>
                <button
                    title="Imprimir relatório de logs de estacionamento"
                    class="js-print-logs flex items-center gap-1 px-3 py-1 bg-white text-blue-700 font-semibold rounded 
                           hover:bg-blue-700 hover:text-white transition-colors duration-300 text-sm"
                    id="btn-print-logs">
                    <i data-lucide="printer" class="w-4 h-4"></i> Imprimir
                </button>
            </div>

            <?php if (!empty($logEntry)): ?>
                <ul class="flex flex-col gap-2 overflow-y-auto max-h-96 pr-2">
                    <?php foreach ($logEntry as $log): ?>
                        <?php
                        $tipoVeiculo = trim(strtolower($log['tipo_veiculo'] ?? 'carro'));
                        switch ($tipoVeiculo) {
                            case 'moto': $icon = 'bike'; break;
                            case 'caminhao': $icon = 'truck'; break;
                            case 'app': $icon = 'smartphone'; break;
                            default: $icon = 'car';
                        }
                        $cor = $log['tipo'] === 'entrada' ? 'text-green-400' : 'text-red-400';
                        ?>
                        <li class="flex justify-between items-center p-2 rounded-lg transition-colors duration-200
                            <?= $log['tipo'] === 'entrada' 
                                ? 'bg-green-600/20 hover:bg-green-600/40' 
                                : 'bg-red-600/20 hover:bg-red-600/40' ?>">
                            <div class="flex items-center gap-3">
                                <i data-lucide="<?= $icon ?>" class="w-5 h-5 <?= $cor ?>"></i>
                                <span class="text-sm font-medium">
                                    <strong><?= htmlspecialchars($log['placa']) ?></strong>
                                    (<?= ucfirst($tipoVeiculo) ?>) <?= $log['tipo'] ?>
                                </span>
                            </div>
                            <span class="text-xs text-gray-300"><?= htmlspecialchars($log['hora']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="text-gray-400 text-center mt-4 text-sm">Nenhum log encontrado hoje.</p>
            <?php endif; ?>
        </div>

    </div>

    <!-- Faturamento -->
    <div class="bg-gradient-to-br from-blue-700/80 to-blue-900/80 p-6 rounded-xl shadow-lg 
                flex flex-col md:flex-row justify-between items-center gap-4 mt-4">

        <div>
            <h1 class="text-white text-2xl font-bold uppercase mb-2 tracking-wide">Faturamento - Dia</h1>
            <span class="text-white text-2xl font-mono">
                R$ <?= number_format(floatval($dailyIncome), 2, ',', '.') ?>
            </span>
        </div>

        <a href="/vacancy"
           class="p-6 bg-white text-blue-700 font-semibold rounded-xl shadow-lg hover:bg-blue-700 
                  hover:text-white transition-colors duration-300 text-center">
            Ver Vagas
        </a>

    </div>

</section>
