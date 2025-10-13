<?php $this->partial('head', ['title' => $title]); ?>

<?php
    // Mapear tipo para imagem e título
    $vehicleTypes = [
        'moto' => ['img' => '/images/moto.png', 'title' => 'Moto'],
        'carro' => ['img' => '/images/car.png', 'title' => 'Carro'],
        'caminhao' => ['img' => '/images/truck.png', 'title' => 'Caminhão'],
    ];

    // Pegar tipo da URL, default para carro
    $type = $_GET['type'] ?? 'carro';
    $vehicle = $vehicleTypes[$type] ?? $vehicleTypes['carro'];
?>

<section class="flex items-center justify-center min-h-screen bg-gradient-to-br from-[#1e2a47] to-[#121826] px-6 py-10">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-10 w-full max-w-5xl">

        <!-- Card pequeno do veículo selecionado -->
        <div class="w-62 h-40 flex flex-col items-center justify-center bg-gradient-to-br from-blue-700/80 to-blue-900/80 rounded-2xl shadow-lg p-4 md:p-6">
            <img src="<?= $vehicle['img'] ?>" alt="<?= $vehicle['title'] ?>" class="w-24 h-24 object-contain mb-2"/>
            <span class="text-white text-lg font-semibold"><?= $vehicle['title'] ?></span>
        </div>

        <!-- Formulário ocupa maior espaço -->
        <div class="md:col-span-3 bg-[#1f2b4a] shadow-lg rounded-2xl px-8 py-10 relative overflow-hidden">
            <h2 class="text-white text-3xl mb-8 font-semibold text-center">🚗 Estacionar <?= $vehicle['title'] ?></h2>

            <?php if (!empty($errorMessage)) : ?>
                <p class="text-red-400 mb-6 text-center"><?= htmlspecialchars($errorMessage) ?></p>
            <?php endif; ?>

            <form action="/vacancy/apply" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>" />
                <input type="hidden" name="id_vaga" value="<?= htmlspecialchars($id_vaga) ?>">

                <!-- Nome completo -->
                <label class="col-span-2 flex flex-col text-white">
                    Nome completo:
                    <div class="relative mt-2">
                        <i data-lucide="user" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-300"></i>
                        <input type="text" name="owner_name" required
                               class="w-full pl-10 pr-4 py-3 rounded-lg bg-[#2d3a5c] text-white placeholder-gray-300
                          focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                               placeholder="Seu nome completo">
                    </div>
                </label>

                <!-- Telefone -->
                <label class="flex flex-col text-white">
                    Telefone:
                    <div class="relative mt-2">
                        <i data-lucide="phone" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-300"></i>
                        <input type="tel" name="phone" required
                               class="w-full pl-10 pr-4 py-3 rounded-lg bg-[#2d3a5c] text-white placeholder-gray-300
                          focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                               placeholder="(99) 99999-9999">
                    </div>
                </label>

                <!-- Placa -->
                <label class="flex flex-col text-white">
                    Placa do veículo:
                    <div class="relative mt-2">
                        <i data-lucide="hash" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-300"></i>
                        <input type="text" name="plate" required maxlength="8"
                               class="w-full pl-10 pr-4 py-3 rounded-lg bg-[#2d3a5c] text-white placeholder-gray-300 uppercase
                          focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                               placeholder="ABC-1234">
                    </div>
                </label>

                <!-- Valor pago -->
                <label class="flex flex-col text-white">
                    Valor pago (R$):
                    <div class="relative mt-2">
                        <i data-lucide="dollar-sign" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-300"></i>
                        <input type="number" name="paid_amount" required step="0.01" min="0"
                               class="w-full pl-10 pr-4 py-3 rounded-lg bg-[#2d3a5c] text-white placeholder-gray-300
                          focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                               placeholder="Ex: 25.00">
                    </div>
                </label>

                <!-- Horário de entrada -->
                <label class="flex flex-col text-white">
                    Horário de entrada:
                    <div class="relative mt-2">
                        <i data-lucide="clock" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-300"></i>
                        <input type="time" name="entry_time" required
                               class="w-full pl-10 pr-4 py-3 rounded-lg bg-[#2d3a5c] text-white placeholder-gray-300
                          focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                    </div>
                </label>

                <!-- Horário de saída (desabilitado) -->
                <label class="flex flex-col text-white opacity-70 cursor-not-allowed">
                    Horário de saída:
                    <div class="relative mt-2">
                        <i data-lucide="clock" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-300"></i>
                        <input type="time" name="exit_time" disabled
                               class="w-full pl-10 pr-4 py-3 rounded-lg bg-[#2d3a5c] text-white placeholder-gray-300
                          focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                               placeholder="Somente ao sair">
                    </div>
                </label>

                <!-- Botão ocupa as duas colunas -->
                <button type="submit"
                        class="col-span-2 w-full py-3 bg-blue-600 hover:bg-blue-700 rounded-lg font-semibold text-white flex items-center justify-center gap-2 transition-colors duration-300">
                    <i data-lucide="check-circle" class="w-5 h-5"></i> Estacionar
                </button>
            </form>

        </div>

    </div>
</section>
