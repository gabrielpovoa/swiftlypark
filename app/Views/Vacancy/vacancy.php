<section class="min-h-screen w-full bg-[#0b0e14] flex flex-col items-center p-6 md:p-12 relative overflow-hidden">

    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[600px] h-[300px] bg-blue-600/10 blur-[120px] rounded-full"></div>

    <header class="relative z-10 text-center mb-16">
        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-blue-500/10 border border-blue-500/20 text-blue-400 text-xs font-bold uppercase tracking-[0.3em] mb-4">
            Status em Tempo Real
        </div>
        <h1 class="text-4xl md:text-6xl font-black text-white tracking-tighter leading-tight">
            Disponibilidade de <span class="bg-gradient-to-r from-blue-400 to-indigo-400 bg-clip-text text-transparent italic">Vagas</span>
        </h1>
    </header>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 w-full max-w-7xl relative z-10">

        <?php
        $items = [
                ['label' => 'CARROS', 'count' => $counts['carro'] ?? 0, 'type' => 'carro', 'icon' => 'car', 'color' => 'blue'],
                ['label' => 'MOTOS', 'count' => $counts['moto'] ?? 0, 'type' => 'moto', 'icon' => 'bike', 'color' => 'emerald'],
                ['label' => 'CAMINHÃO', 'count' => $counts['caminhao'] ?? 0, 'type' => 'caminhao', 'icon' => 'truck', 'color' => 'indigo'],
                ['label' => 'APPS', 'count' => $counts['app'] ?? 0, 'type' => 'app', 'icon' => 'smartphone', 'color' => 'amber', 'popular' => true],
        ];

        foreach ($items as $item):
            $colorClass = [
                    'blue' => 'text-blue-500 bg-blue-500/10 border-blue-500/20',
                    'emerald' => 'text-emerald-500 bg-emerald-500/10 border-emerald-500/20',
                    'indigo' => 'text-indigo-500 bg-indigo-500/10 border-indigo-500/20',
                    'amber' => 'text-amber-500 bg-amber-500/10 border-amber-500/20',
            ][$item['color']];
            ?>

            <?php if (!empty($canCheckin)): ?>
            <a href="/vacancy/apply?type=<?= $item['type'] ?>"
               class="group relative rounded-[2.5rem] bg-white/[0.03] border border-white/5 p-8 flex flex-col items-center
                      transition-all duration-500 hover:bg-white/[0.06] hover:-translate-y-2 hover:shadow-[0_20px_40px_rgba(0,0,0,0.3)]">

                <?php if (!empty($item['popular'])): ?>
                    <div class="absolute -top-3 px-4 py-1 bg-amber-500 text-black font-black text-[10px] uppercase tracking-tighter rounded-full shadow-lg shadow-amber-500/20">
                        Mais Procurado ★
                    </div>
                <?php endif; ?>

                <div class="mb-6 p-5 rounded-2xl <?= $colorClass ?> border transition-transform duration-500 group-hover:scale-110">
                    <i data-lucide="<?= $item['icon'] ?>" class="w-10 h-10"></i>
                </div>

                <h2 class="text-sm font-bold tracking-[0.2em] text-slate-500 group-hover:text-slate-300 transition-colors">
                    <?= $item['label'] ?>
                </h2>

                <div class="flex items-baseline gap-1 mt-4">
                    <span class="text-7xl font-black text-white tracking-tighter transition-all duration-500 group-hover:text-blue-400">
                        <?= $item['count'] ?>
                    </span>
                </div>

                <p class="text-xs font-bold uppercase tracking-widest text-slate-600 mt-2">vagas livres</p>

                <div class="mt-8 w-12 h-1.5 rounded-full bg-white/5 overflow-hidden">
                    <div class="h-full bg-blue-500 w-1/3 group-hover:w-full transition-all duration-700"></div>
                </div>
            </a>
            <?php else: ?>
            <div class="group relative rounded-[2.5rem] bg-white/[0.03] border border-white/5 p-8 flex flex-col items-center">
                <div class="mb-6 p-5 rounded-2xl <?= $colorClass ?> border">
                    <i data-lucide="<?= $item['icon'] ?>" class="w-10 h-10"></i>
                </div>
                <h2 class="text-sm font-bold tracking-[0.2em] text-slate-500"><?= $item['label'] ?></h2>
                <span class="text-7xl font-black text-white tracking-tighter mt-4"><?= $item['count'] ?></span>
                <p class="text-xs font-bold uppercase tracking-widest text-slate-600 mt-2">vagas livres</p>
            </div>
            <?php endif; ?>

        <?php endforeach; ?>
    </div>

    <div class="flex flex-wrap justify-center gap-6 mt-20 relative z-10">
        <a href="/vacancy/manage"
           class="group flex items-center gap-3 px-8 py-4 rounded-2xl bg-white/5 border border-white/10 text-white font-bold
                  transition-all duration-300 hover:bg-blue-600 hover:border-blue-500 hover:shadow-[0_0_20px_rgba(37,99,235,0.3)]">
            <i data-lucide="layout-grid" class="w-5 h-5 text-blue-400 group-hover:text-white"></i>
            Gerenciar Vagas
        </a>

        <?php if (!empty($canCreateVacancy)): ?>
        <a href="/CreateVacancy"
           class="group flex items-center gap-3 px-8 py-4 rounded-2xl bg-blue-600 text-white font-bold
                  transition-all duration-300 hover:bg-blue-500 hover:shadow-[0_0_20px_rgba(37,99,235,0.4)] active:scale-95">
            <i data-lucide="plus" class="w-5 h-5 group-hover:rotate-90 transition-transform duration-500"></i>
            Adicionar Novas Vagas
        </a>
        <?php endif; ?>
    </div>

</section>
