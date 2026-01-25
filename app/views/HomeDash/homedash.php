<?php $this->partial('head', ['title' => $title]); ?>

<section class="min-h-screen w-full bg-[#0b0e14] text-slate-300 p-4 md:p-8 relative overflow-hidden">

    <div class="absolute top-[-10%] right-[-5%] w-[400px] h-[400px] bg-blue-600/5 blur-[120px] rounded-full pointer-events-none"></div>
    <div class="absolute bottom-[-5%] left-[-5%] w-[300px] h-[300px] bg-indigo-600/5 blur-[120px] rounded-full pointer-events-none"></div>

    <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8 relative z-10">

        <aside class="lg:col-span-3 space-y-6">
            <div class="flex items-center gap-2 mb-4 ml-1">
                <div class="w-1.5 h-4 bg-blue-500 rounded-full"></div>
                <h2 class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-500">Check-in Rápido</h2>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-1 gap-4">
                <?php
                $quickActions = [
                        ['moto', 'Moto', 'text-amber-400', 'bg-amber-400/10', 'border-amber-400/20'],
                        ['carro', 'Carro', 'text-blue-400', 'bg-blue-400/10', 'border-blue-400/20'],
                        ['caminhao', 'Caminhão', 'text-emerald-400', 'bg-emerald-400/10', 'border-emerald-400/20'],
                        ['app', 'App/Uber', 'text-indigo-400', 'bg-indigo-400/10', 'border-indigo-400/20'],
                ];

                foreach ($quickActions as $action): ?>
                    <a href="/vacancy/apply?type=<?= $action[0] ?>"
                       class="group relative flex flex-col items-center justify-center bg-white/[0.02] border border-white/5 rounded-[2.5rem] p-6 transition-all duration-500
                          hover:bg-white/[0.05] hover:border-white/20 hover:-translate-y-2 hover:shadow-[0_20px_40px_rgba(0,0,0,0.4)]">

                        <div class="p-4 rounded-2xl <?= $action[3] ?> mb-3 transition-transform duration-500 group-hover:scale-110">
                            <i data-lucide="<?= ($action[0] === 'moto' ? 'bike' : ($action[0] === 'carro' ? 'car' : ($action[0] === 'caminhao' ? 'truck' : 'smartphone'))) ?>"
                               class="w-8 h-8 <?= $action[2] ?>"></i>
                        </div>

                        <span class="text-white text-xs font-black uppercase tracking-widest group-hover:text-blue-400 transition-colors">
                        <?= $action[1] ?>
                    </span>

                        <div class="absolute bottom-4 w-1 h-1 rounded-full bg-blue-500 opacity-0 group-hover:opacity-100 transition-all group-hover:w-8"></div>
                    </a>
                <?php endforeach; ?>
            </div>
        </aside>

        <main class="lg:col-span-9 flex flex-col gap-6">

            <div class="flex-grow bg-white/[0.01] backdrop-blur-3xl border border-white/5 rounded-[3rem] p-8 shadow-2xl flex flex-col min-h-[550px]">

                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-10 border-b border-white/5 pb-8">
                    <div>
                        <h2 class="text-3xl font-black text-white tracking-tighter italic">Fluxo <span class="text-blue-500">Recente</span></h2>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="flex h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Live Monitoring System</p>
                        </div>
                    </div>

                    <button id="btn-print-logs"
                            class="js-print-logs relative group flex items-center gap-3 px-8 py-4 bg-white/5 hover:bg-white/10 text-white text-xs font-black uppercase tracking-[0.2em] rounded-2xl border border-white/10 transition-all active:scale-95 overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-r from-blue-600/20 to-indigo-600/20 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        <i data-lucide="printer" class="w-4 h-4 text-blue-400 group-hover:rotate-12 transition-transform"></i>
                        <span class="relative">Relatório</span>
                    </button>
                </div>

                <div class="overflow-y-auto pr-4 custom-scrollbar space-y-4">
                    <?php if (!empty($logEntry)): ?>
                        <?php foreach ($logEntry as $log):
                            $isEntrada = $log['tipo'] === 'entrada';
                            $tipoVeiculo = trim(strtolower($log['tipo_veiculo'] ?? 'carro'));
                            $icons = ['moto' => 'bike', 'caminhao' => 'truck', 'app' => 'smartphone', 'carro' => 'car'];
                            $iconName = $icons[$tipoVeiculo] ?? 'car';
                            ?>
                            <div class="group flex items-center justify-between p-5 bg-white/[0.02] border border-white/5 rounded-[2rem] hover:bg-white/[0.04] hover:border-white/10 transition-all duration-300">
                                <div class="flex items-center gap-5">
                                    <div class="relative">
                                        <div class="p-4 rounded-2xl <?= $isEntrada ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400' ?> group-hover:scale-110 transition-transform duration-500">
                                            <i data-lucide="<?= $iconName ?>" class="w-5 h-5"></i>
                                        </div>
                                        <div class="absolute -top-1 -right-1 w-3 h-3 rounded-full border-2 border-[#0b0e14] <?= $isEntrada ? 'bg-emerald-500' : 'bg-rose-500' ?>"></div>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-3">
                                            <span class="text-xl font-black text-white tracking-widest font-mono"><?= strtoupper($log['placa']) ?></span>
                                            <span class="px-3 py-0.5 rounded-full bg-white/5 text-[9px] font-black uppercase tracking-tighter text-slate-400 border border-white/5"><?= $tipoVeiculo ?></span>
                                        </div>
                                        <p class="text-[9px] font-black uppercase tracking-[0.2em] mt-1 <?= $isEntrada ? 'text-emerald-500/80' : 'text-rose-500/80' ?>">
                                            <?= $isEntrada ? 'Check-in Realizado' : 'Check-out Finalizado' ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="block text-sm font-black text-white tracking-tighter"><?= htmlspecialchars($log['hora']) ?></span>
                                    <span class="text-[9px] text-slate-600 font-bold uppercase">Timestamp</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="flex flex-col items-center justify-center py-20">
                            <div class="w-20 h-20 bg-white/[0.02] rounded-full flex items-center justify-center mb-4 border border-white/5">
                                <i data-lucide="inbox" class="w-8 h-8 text-slate-700"></i>
                            </div>
                            <p class="text-xs font-bold uppercase tracking-widest text-slate-600 italic">Nenhum registro hoje</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="md:col-span-2 relative overflow-hidden group bg-blue-600 rounded-[2.5rem] p-8 transition-all duration-500 hover:shadow-[0_20px_40px_rgba(37,99,235,0.3)]">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 blur-3xl rounded-full -mr-16 -mt-16 group-hover:scale-150 transition-transform duration-700"></div>
                    <div class="relative z-10 flex items-center justify-between">
                        <div>
                            <p class="text-blue-200 text-[10px] font-black uppercase tracking-[0.3em] mb-2">Revenue Today</p>
                            <h3 class="text-5xl font-black text-white tracking-tighter italic">
                                R$ <?= number_format(floatval($dailyIncome), 2, ',', '.') ?>
                            </h3>
                        </div>
                        <div class="bg-black/20 p-5 rounded-3xl backdrop-blur-md border border-white/10 group-hover:rotate-12 transition-transform">
                            <i data-lucide="trending-up" class="w-8 h-8 text-white"></i>
                        </div>
                    </div>
                </div>

                <a href="/vacancy"
                   class="relative group bg-white rounded-[2.5rem] p-8 flex flex-col items-center justify-center text-center transition-all duration-500 hover:bg-blue-50 hover:-translate-y-2">
                    <div class="w-14 h-14 bg-blue-600/10 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <i data-lucide="layout-grid" class="w-6 h-6 text-blue-600"></i>
                    </div>
                    <span class="text-[#0b0e14] font-black text-[10px] uppercase tracking-widest">Painel de Vagas</span>
                    <i data-lucide="arrow-right" class="w-4 h-4 text-blue-600 mt-2 opacity-0 group-hover:opacity-100 group-hover:translate-x-2 transition-all"></i>
                </a>
            </div>
        </main>
    </div>
</section>

<style>
    /* Scrollbar personalizada para o tema escuro */
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.05);
        border-radius: 20px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: rgba(59, 130, 246, 0.5);
    }

    /* Animação suave para as bordas neon */
    @keyframes border-pulse {
        0%, 100% { border-color: rgba(255,255,255,0.05); }
        50% { border-color: rgba(59, 130, 246, 0.3); }
    }
</style>