<?php $this->partial('head', ['title' => $title]); ?>

<section class="min-h-screen w-full bg-[#0b0e14] flex items-center justify-center p-6 md:p-12 relative overflow-hidden">

    <div class="absolute top-0 left-1/4 w-[500px] h-[500px] bg-blue-600/10 blur-[120px] rounded-full"></div>
    <div class="absolute bottom-0 right-1/4 w-[400px] h-[400px] bg-indigo-600/10 blur-[100px] rounded-full"></div>

    <div class="max-w-6xl w-full grid grid-cols-1 lg:grid-cols-12 gap-8 items-stretch relative z-10">

        <div class="lg:col-span-7 bg-white/[0.03] backdrop-blur-xl border border-white/10 rounded-[2.5rem] p-8 md:p-14 shadow-2xl flex flex-col justify-center">

            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-blue-500/10 border border-blue-500/20 text-blue-400 text-xs font-bold uppercase tracking-widest mb-8 w-fit">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
                </span>
                Versão 2.4 Stable
            </div>

            <h1 class="text-4xl md:text-6xl font-black text-white tracking-tighter leading-[1.1] mb-8">
                Gestão inteligente para <span class="bg-gradient-to-r from-blue-400 to-indigo-400 bg-clip-text text-transparent italic">estacionamentos.</span>
            </h1>

            <div class="space-y-6">
                <p class="text-lg md:text-xl text-slate-400 leading-relaxed font-medium">
                    O <span class="text-white font-bold">SwiftlyPark</span> redefine a forma como você gerencia vagas,
                    unindo performance de alto nível com uma interface que qualquer um sabe usar.
                </p>

                <div class="flex flex-wrap gap-4 pt-4">
                    <div class="flex items-center gap-2 bg-white/5 px-4 py-2 rounded-xl border border-white/5">
                        <i data-lucide="zap" class="w-4 h-4 text-yellow-400"></i>
                        <span class="text-sm font-bold text-slate-300">Alta Performance</span>
                    </div>
                    <div class="flex items-center gap-2 bg-white/5 px-4 py-2 rounded-xl border border-white/5">
                        <i data-lucide="shield-check" class="w-4 h-4 text-emerald-400"></i>
                        <span class="text-sm font-bold text-slate-300">Segurança PHP 8.3</span>
                    </div>
                </div>
            </div>

            <div class="mt-12 flex flex-col sm:flex-row gap-4">
                <a href="/landingPage"
                   class="group px-8 py-4 bg-blue-600 hover:bg-blue-500 rounded-2xl font-bold text-white shadow-lg shadow-blue-600/20 transition-all duration-300 flex items-center justify-center gap-3 active:scale-95">
                    Conheça o Sistema
                    <i data-lucide="chevron-right" class="w-5 h-5 group-hover:translate-x-1 transition-transform"></i>
                </a>
                <button class="px-8 py-4 bg-white/5 hover:bg-white/10 border border-white/10 rounded-2xl font-bold text-slate-300 transition-all">
                    Documentação
                </button>
            </div>
        </div>

        <div class="lg:col-span-5 grid grid-cols-1 gap-4">

            <div class="bg-gradient-to-br from-blue-600 to-indigo-700 rounded-[2.5rem] p-8 text-white shadow-xl flex flex-col justify-between">
                <i data-lucide="layers" class="w-12 h-12 p-2 bg-white/20 rounded-xl mb-6"></i>
                <div>
                    <h3 class="text-2xl font-bold mb-3 tracking-tight">Recursos Premium</h3>
                    <p class="text-blue-100/80 leading-relaxed">
                        Controle total com monitoramento em tempo real, emissão de tickets inteligentes e relatórios automáticos.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="bg-white/[0.03] border border-white/10 rounded-[2rem] p-6 hover:bg-white/[0.06] transition-colors">
                    <i data-lucide="monitor" class="w-6 h-6 text-blue-400 mb-3"></i>
                    <p class="text-xs font-bold text-white uppercase tracking-wider">Dashboard</p>
                </div>
                <div class="bg-white/[0.03] border border-white/10 rounded-[2rem] p-6 hover:bg-white/[0.06] transition-colors">
                    <i data-lucide="file-text" class="w-6 h-6 text-indigo-400 mb-3"></i>
                    <p class="text-xs font-bold text-white uppercase tracking-wider">Relatórios</p>
                </div>
                <div class="relative group bg-white/[0.01] border border-white/5 rounded-[2rem] p-6 overflow-hidden cursor-help">
                    <div class="absolute inset-0 bg-[#0b0e14]/40 backdrop-blur-[2px] z-10"></div>

                    <div class="absolute top-3 right-3 z-20">
                        <span class="text-[8px] font-black uppercase tracking-tighter bg-blue-500/20 text-blue-400 px-2 py-1 rounded-md border border-blue-500/30">
                            In Process
                        </span>
                    </div>

                    <div class="relative z-0 opacity-30 flex flex-col items-start">
                        <i data-lucide="smartphone" class="w-6 h-6 text-slate-500 mb-3"></i>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Responsivo</p>
                    </div>

                    <div class="absolute inset-0 z-20 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                        <i data-lucide="construction" class="w-8 h-8 text-blue-500/50"></i>
                    </div>
                </div>
                <div class="bg-white/[0.03] border border-white/10 rounded-[2rem] p-6 hover:bg-white/[0.06] transition-colors">
                    <i data-lucide="users" class="w-6 h-6 text-purple-400 mb-3"></i>
                    <p class="text-xs font-bold text-white uppercase tracking-wider">Multi-usuários</p>
                </div>
            </div>
        </div>

    </div>
</section>