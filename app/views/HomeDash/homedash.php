<?php $this->partial('head', ['title' => $title]); ?>

<section class="min-h-screen w-full bg-[#0b0e14] text-slate-300 p-4 md:p-8">

    <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-6">

        <aside class="lg:col-span-3 flex flex-col gap-4">
            <h2 class="text-xs font-bold uppercase tracking-[0.2em] text-slate-500 mb-2 ml-1">Entrada Rápida</h2>

            <div class="grid grid-cols-2 lg:grid-cols-1 gap-4">
                <a href="/vacancy/apply?type=moto"
                   class="group flex flex-col items-center justify-center bg-white/[0.03] border border-white/10 rounded-3xl p-6 transition-all duration-300 hover:bg-blue-600 hover:border-blue-400 hover:-translate-y-1 shadow-xl">
                    <div class="p-3 bg-white/5 rounded-2xl mb-3 group-hover:bg-white/20 transition-colors">
                        <img src="/images/moto.png" alt="Moto" class="w-16 h-16 object-contain"/>
                    </div>
                    <span class="text-white font-bold tracking-wide">Moto</span>
                </a>

                <a href="/vacancy/apply?type=carro"
                   class="group flex flex-col items-center justify-center bg-white/[0.03] border border-white/10 rounded-3xl p-6 transition-all duration-300 hover:bg-blue-600 hover:border-blue-400 hover:-translate-y-1 shadow-xl">
                    <div class="p-3 bg-white/5 rounded-2xl mb-3 group-hover:bg-white/20 transition-colors">
                        <img src="/images/car.png" alt="Carro" class="w-16 h-16 object-contain"/>
                    </div>
                    <span class="text-white font-bold tracking-wide">Carro</span>
                </a>

                <a href="/vacancy/apply?type=caminhao"
                   class="group flex flex-col items-center justify-center bg-white/[0.03] border border-white/10 rounded-3xl p-6 transition-all duration-300 hover:bg-blue-600 hover:border-blue-400 hover:-translate-y-1 shadow-xl">
                    <img src="/images/truck.png" alt="Caminhão" class="w-16 h-16 object-contain mb-3 drop-shadow-lg"/>
                    <span class="text-white font-bold tracking-wide text-sm">Caminhão</span>
                </a>

                <a href="/vacancy/apply?type=app"
                   class="group flex flex-col items-center justify-center bg-white/[0.03] border border-white/10 rounded-3xl p-6 transition-all duration-300 hover:bg-blue-600 hover:border-blue-400 hover:-translate-y-1 shadow-xl">
                    <img src="/images/uber.png" alt="App/Uber" class="w-16 h-16 object-contain mb-3 drop-shadow-lg"/>
                    <span class="text-white font-bold tracking-wide text-sm">App/Uber</span>
                </a>
            </div>
        </aside>

        <main class="lg:col-span-9 flex flex-col gap-6">

            <div class="flex-grow bg-white/[0.03] border border-white/10 rounded-3xl p-8 shadow-2xl flex flex-col min-h-[500px]">

                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8 border-b border-white/5 pb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-white tracking-tight">Atividade Recente</h2>
                        <p class="text-sm text-slate-500">Fluxo de entradas e saídas em tempo real</p>
                    </div>

                    <button id="btn-print-logs"
                            class="js-print-logs flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-500 text-white text-sm font-bold rounded-xl transition-all shadow-lg shadow-blue-600/20 active:scale-95">
                        <i data-lucide="printer" class="w-4 h-4"></i>
                        Imprimir Relatório
                    </button>
                </div>

                <div class="overflow-y-auto pr-2 custom-scrollbar">
                    <?php if (!empty($logEntry)): ?>
                        <div class="space-y-3">
                            <?php foreach ($logEntry as $log):
                                $isEntrada = $log['tipo'] === 'entrada';
                                $tipoVeiculo = trim(strtolower($log['tipo_veiculo'] ?? 'carro'));
                                $icons = ['moto' => 'bike', 'caminhao' => 'truck', 'app' => 'smartphone', 'carro' => 'car'];
                                $iconName = $icons[$tipoVeiculo] ?? 'car';
                                ?>
                                <div class="flex items-center justify-between p-4 bg-white/[0.02] border border-white/5 rounded-2xl hover:bg-white/[0.05] transition-colors">
                                    <div class="flex items-center gap-4">
                                        <div class="p-3 rounded-xl <?= $isEntrada ? 'bg-emerald-500/10 text-emerald-500' : 'bg-rose-500/10 text-rose-500' ?>">
                                            <i data-lucide="<?= $iconName ?>" class="w-5 h-5"></i>
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="text-lg font-mono font-black text-white"><?= strtoupper($log['placa']) ?></span>
                                                <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded bg-white/5 text-slate-400"><?= ucfirst($tipoVeiculo) ?></span>
                                            </div>
                                            <p class="text-xs font-medium uppercase tracking-tighter <?= $isEntrada ? 'text-emerald-500' : 'text-rose-500' ?>">
                                                <?= $isEntrada ? '📥 Entrada Realizada' : '📤 Saída Finalizada' ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span class="block text-sm font-bold text-slate-200"><?= htmlspecialchars($log['hora']) ?></span>
                                        <span class="text-[10px] text-slate-500 uppercase font-bold">Hoje</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="flex flex-col items-center justify-center py-20 text-slate-600">
                            <i data-lucide="clipboard-list" class="w-12 h-12 mb-4 opacity-20"></i>
                            <p>Nenhuma movimentação registrada hoje.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                <div class="md:col-span-2 bg-gradient-to-br from-blue-600 to-indigo-700 rounded-3xl p-6 flex items-center justify-between shadow-xl shadow-blue-900/20">
                    <div>
                        <p class="text-blue-100 text-xs font-bold uppercase tracking-widest mb-1">Receita do Dia</p>
                        <h3 class="text-4xl font-black text-white tracking-tighter">
                            R$ <?= number_format(floatval($dailyIncome), 2, ',', '.') ?>
                        </h3>
                    </div>
                    <div class="bg-white/10 p-4 rounded-2xl">
                        <i data-lucide="trending-up" class="w-8 h-8 text-white"></i>
                    </div>
                </div>

                <a href="/vacancy"
                   class="bg-white text-[#0b0e14] rounded-3xl p-6 flex flex-col items-center justify-center text-center hover:bg-slate-200 transition-all group shadow-xl">
                    <i data-lucide="layout-grid" class="w-6 h-6 mb-2 group-hover:scale-110 transition-transform"></i>
                    <span class="font-bold text-sm">Monitorar Vagas</span>
                </a>

            </div>
        </main>
    </div>

</section>

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.2); }
</style>
