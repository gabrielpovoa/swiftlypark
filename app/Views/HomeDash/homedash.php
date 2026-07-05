<?php $this->partial('head', ['title' => $title]); ?>

<section class="h-screen w-full bg-[#0b0e14] text-slate-300 p-6 md:p-8 relative overflow-hidden flex flex-col">

    <div class="absolute top-[-10%] right-[-5%] w-[400px] h-[400px] bg-blue-600/5 blur-[120px] rounded-full pointer-events-none"></div>

    <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8 relative z-10 w-full h-full overflow-hidden">

        <?php if (!empty($canCheckin)): ?>
        <aside class="lg:col-span-3 flex flex-col h-full">
            <div class="flex items-center gap-2 mb-6 ml-1">
                <div class="w-2 h-5 bg-blue-500 rounded-full shadow-[0_0_15px_rgba(59,130,246,0.5)]"></div>
                <h2 class="text-xs font-black uppercase tracking-[0.3em] text-slate-500">Check-in Rápido</h2>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-1 gap-4 overflow-y-auto overflow-x-visible pr-1 custom-scrollbar">
                <?php
                $quickActions = [
                        ['moto', 'Moto', 'text-amber-400', 'bg-amber-400/10'],
                        ['carro', 'Carro', 'text-blue-400', 'bg-blue-400/10'],
                        ['caminhao', 'Caminhão', 'text-emerald-400', 'bg-emerald-400/10'],
                        ['app', 'App/Uber', 'text-indigo-400', 'bg-indigo-400/10'],
                ];

                foreach ($quickActions as $action): ?>
                    <a href="/vacancy/apply?type=<?= $action[0] ?>"
                       class="group relative flex flex-col items-center justify-center bg-white/[0.03] border border-white/5 rounded-[2.5rem] p-7 transition-all duration-500
                          hover:bg-white/[0.08] hover:border-blue-500/30 hover:-translate-y-1 hover:scale-[1.02] shadow-2xl overflow-visible">
                        <div class="p-4 rounded-2xl <?= $action[3] ?> mb-3 transition-transform duration-500 group-hover:scale-110 group-hover:rotate-3">
                            <i data-lucide="<?= ($action[0] === 'moto' ? 'bike' : ($action[0] === 'carro' ? 'car' : ($action[0] === 'caminhao' ? 'truck' : 'smartphone'))) ?>"
                               class="w-8 h-8 <?= $action[2] ?>"></i>
                        </div>
                        <span class="text-white text-sm font-black uppercase tracking-widest group-hover:text-blue-400 transition-colors">
                            <?= $action[1] ?>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        </aside>
        <?php endif; ?>

        <main class="<?= !empty($canCheckin) ? 'lg:col-span-9' : 'lg:col-span-12' ?> flex flex-col gap-6 h-full overflow-hidden">

            <div class="flex-1 bg-white/[0.01] backdrop-blur-3xl border border-white/5 rounded-[3rem] p-8 shadow-2xl flex flex-col overflow-hidden">

                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8 border-b border-white/5 pb-8 flex-shrink-0">
                    <div>
                        <h2 class="text-4xl font-black text-white tracking-tighter italic">Fluxo <span class="text-blue-500">do Mês</span></h2>
                        <div class="flex items-center gap-2 mt-2">
                            <span class="flex h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500 font-mono">Entradas e saídas do mês atual</p>
                        </div>
                    </div>

                    <?php if (!empty($canViewReports)): ?>
                    <button id="btn-print-logs"
                            class="js-print-logs relative group flex items-center gap-3 px-8 py-4 bg-white/5 hover:bg-white/10 text-white text-xs font-black uppercase tracking-[0.2em] rounded-2xl border border-white/10 transition-all active:scale-95 cursor-pointer">
                        <i data-lucide="printer" class="w-4 h-4 text-blue-400 group-hover:rotate-12 transition-transform"></i>
                        <span>Relatório</span>
                    </button>
                    <?php endif; ?>
                </div>

                <div class="flex-1 overflow-y-auto pr-3 custom-scrollbar space-y-2">
                    <?php if (!empty($logEntry)): ?>
                        <?php foreach ($logEntry as $log):
                            $isEntrada = $log['tipo'] === 'entrada';
                            $tipoVeiculo = trim(strtolower($log['tipo_veiculo'] ?? 'carro'));
                            $icons = ['moto' => 'bike', 'caminhao' => 'truck', 'app' => 'smartphone', 'carro' => 'car'];
                            $iconName = $icons[$tipoVeiculo] ?? 'car';
                            ?>
                            <div class="group flex items-center justify-between py-2 px-4 bg-white/[0.01] border border-white/5 rounded-xl hover:bg-white/[0.04] transition-all duration-300">
                                <div class="flex items-center gap-4">
                                    <div class="p-2 rounded-lg <?= $isEntrada ? 'bg-emerald-500/10 text-emerald-500' : 'bg-rose-500/10 text-rose-400' ?>">
                                        <i data-lucide="<?= $iconName ?>" class="w-3.5 h-3.5"></i>
                                    </div>

                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-base font-black text-white font-mono tracking-wider"><?= strtoupper($log['placa']) ?></span>
                                            <span class="text-[8px] font-bold px-1.5 py-0.5 rounded bg-white/5 text-slate-500 border border-white/5 uppercase"><?= $tipoVeiculo ?></span>
                                        </div>
                                        <p class="text-[8px] font-bold uppercase tracking-tighter opacity-50 italic">
                                            <?= $isEntrada ? 'Check-in detectado' : 'Check-out detectado' ?>
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-4">
                                <span class="text-[8px] font-black uppercase tracking-widest px-2 py-1 rounded-md <?= $isEntrada ? 'text-emerald-500 bg-emerald-500/5 border border-emerald-500/20' : 'text-rose-500 bg-rose-500/5 border border-rose-500/20' ?>">
                                    <?= $isEntrada ? 'Entrada' : 'Saída' ?>
                                </span>
                                    <div class="text-right min-w-[60px]">
                                        <span class="block text-xs font-black text-white tracking-tighter"><?= $log['hora'] ?></span>
                                        <span class="block text-[8px] font-bold text-slate-500"><?= $log['data'] ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="h-full flex flex-col items-center justify-center opacity-20">
                            <i data-lucide="activity" class="w-12 h-12 mb-2"></i>
                            <p class="text-xs font-black uppercase tracking-widest italic">Aguardando movimentação...</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 h-36 flex-shrink-0 mb-2">
                <div class="md:col-span-2 relative overflow-hidden group bg-blue-600 rounded-[3rem] px-10 flex items-center justify-between shadow-[0_20px_50px_rgba(37,99,235,0.25)]">
                    <div class="absolute top-0 right-0 w-48 h-48 bg-white/10 blur-[80px] rounded-full -mr-24 -mt-24 group-hover:scale-150 transition-transform duration-700"></div>
                    <div>
                        <p class="text-blue-100 text-[10px] font-black uppercase tracking-[0.4em] mb-2 opacity-80">Receita Total do Mês</p>
                        <h3 class="text-5xl font-black text-white tracking-tighter italic">
                            R$ <?= number_format(floatval($monthlyIncome), 2, ',', '.') ?>
                        </h3>
                    </div>
                    <div class="bg-black/20 p-5 rounded-[2rem] border border-white/10 backdrop-blur-md group-hover:rotate-12 transition-transform shadow-2xl">
                        <i data-lucide="trending-up" class="w-8 h-8 text-white"></i>
                    </div>
                </div>

                <a href="/vacancy"
                   class="relative group bg-white rounded-[3rem] flex flex-col items-center justify-center text-center transition-all duration-500 hover:bg-blue-50 hover:-translate-y-2 shadow-xl shadow-white/5">
                    <div class="w-16 h-16 bg-blue-600/10 rounded-3xl flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                        <i data-lucide="layout-grid" class="w-7 h-7 text-blue-600"></i>
                    </div>
                    <span class="text-[#0b0e14] font-black text-xs uppercase tracking-[0.2em]">Vagas</span>
                </a>
            </div>
        </main>
    </div>
</section>

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.05);
        border-radius: 20px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: rgba(59, 130, 246, 0.5);
    }
</style>
