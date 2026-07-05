<?php $this->partial('head', ['title' => $title]); ?>

    <section class="min-h-screen w-full bg-[#0b0e14] flex items-center justify-center p-6 relative overflow-hidden">

        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-blue-600/5 blur-[120px] rounded-full animate-pulse"></div>

        <div class="max-w-2xl w-full relative z-10">

            <div class="bg-white/[0.02] backdrop-blur-3xl border border-white/5 shadow-[0_25px_50px_rgba(0,0,0,0.5)] rounded-[3rem] p-10 md:p-16 text-center">

                <div class="relative inline-flex mb-10">
                    <div class="absolute inset-0 bg-blue-500/20 blur-2xl rounded-full"></div>
                    <div class="relative w-24 h-24 md:w-32 md:h-32 rounded-[2rem] bg-gradient-to-br from-blue-600 to-indigo-700 flex items-center justify-center shadow-2xl transform -rotate-6">
                        <i data-lucide="ban" class="w-12 h-12 md:w-16 md:h-16 text-white opacity-90"></i>
                    </div>
                    <span class="absolute -bottom-2 -right-2 bg-rose-500 text-white text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-widest shadow-lg">
                    Lotação Máxima
                </span>
                </div>

                <h1 class="text-4xl md:text-5xl font-black text-white tracking-tighter italic mb-6">
                    Pátio <span class="text-blue-500 uppercase">Ocupado</span>
                </h1>

                <div class="space-y-4 mb-10">
                    <p class="text-slate-300 text-lg font-medium leading-relaxed">
                        No momento, todas as vagas para
                        <span class="px-3 py-1 bg-blue-500/10 border border-blue-500/20 text-blue-400 rounded-lg font-bold italic">
                        <?= htmlspecialchars($type) ?>
                    </span>
                        estão em uso.
                    </p>

                    <div class="flex items-center justify-center gap-2 text-slate-500">
                    <span class="relative flex h-2 w-2">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                        <p class="text-[10px] font-black uppercase tracking-[0.2em]">Monitoramento em tempo real ativo</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php if (!empty($canCreateVacancy)): ?>
                    <a href="/CreateVacancy"
                       class="group flex flex-col items-center justify-center p-6 bg-white/[0.03] border border-white/5 rounded-[2rem] hover:bg-blue-600 transition-all duration-500 shadow-xl">
                        <i data-lucide="plus-circle" class="w-6 h-6 text-blue-500 group-hover:text-white mb-2 transition-colors"></i>
                        <span class="text-white font-bold text-xs uppercase tracking-widest">Expandir Vagas</span>
                    </a>
                    <?php endif; ?>

                    <a href="/"
                       class="group flex flex-col items-center justify-center p-6 bg-white/[0.03] border border-white/5 rounded-[2rem] hover:bg-slate-800 transition-all duration-500 shadow-xl">
                        <i data-lucide="layout-dashboard" class="w-6 h-6 text-slate-500 group-hover:text-white mb-2 transition-colors"></i>
                        <span class="text-slate-400 group-hover:text-white font-bold text-xs uppercase tracking-widest">Ver Painel</span>
                    </a>
                </div>

                <div class="mt-12 pt-8 border-t border-white/5">
                    <p class="text-slate-600 text-xs font-medium">
                        Precisa de liberação emergencial?
                        <a href="/Contact" class="text-blue-500 hover:text-blue-400 font-bold underline underline-offset-4">Fale com o Suporte</a>
                    </p>
                </div>
            </div>
        </div>
    </section>
