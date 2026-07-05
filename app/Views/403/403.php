<?php $this->partial('head', ['title' => $title]); ?>

<section class="relative min-h-screen w-full bg-[#0b0e14] overflow-hidden flex items-center justify-center p-6">
    <div class="absolute -top-24 -right-24 w-96 h-96 bg-indigo-600/10 blur-[120px] rounded-full pointer-events-none"></div>
    <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-rose-600/10 blur-[120px] rounded-full pointer-events-none"></div>

    <main class="relative z-10 w-full max-w-5xl grid grid-cols-1 lg:grid-cols-2 items-center gap-10 lg:gap-16">
        <div class="order-2 lg:order-1 text-center lg:text-left">
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-rose-500/10 border border-rose-500/20 mb-6">
                <i data-lucide="shield-alert" class="w-4 h-4 text-rose-400"></i>
                <span class="text-[10px] font-black uppercase tracking-[0.25em] text-rose-400">Erro 403 · Acesso restrito</span>
            </div>

            <h1 class="text-4xl md:text-6xl font-black text-white tracking-tighter leading-tight">
                Esta área exige uma
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-indigo-400 italic">permissão especial</span>
            </h1>

            <p class="text-slate-400 text-base md:text-lg leading-relaxed mt-6 max-w-xl">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                Se você acredita que deveria ter acesso, fale com um administrador do sistema.
            </p>

            <div class="flex flex-col sm:flex-row justify-center lg:justify-start gap-3 mt-9">
                <button type="button" onclick="history.back()"
                        class="group inline-flex items-center justify-center gap-3 px-7 py-4 rounded-2xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-black uppercase tracking-widest transition-all active:scale-95 shadow-lg shadow-blue-600/20">
                    <i data-lucide="arrow-left" class="w-4 h-4 transition-transform group-hover:-translate-x-1"></i>
                    Voltar à página anterior
                </button>

                <a href="/login/logout"
                   class="inline-flex items-center justify-center gap-3 px-7 py-4 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/10 text-slate-300 text-xs font-black uppercase tracking-widest transition-all">
                    <i data-lucide="log-out" class="w-4 h-4"></i>
                    Trocar de usuário
                </a>
            </div>

            <div class="flex items-center justify-center lg:justify-start gap-2 mt-8 text-slate-600">
                <i data-lucide="lock-keyhole" class="w-4 h-4"></i>
                <span class="text-[10px] font-bold uppercase tracking-[0.2em]">SwiftlyPark · Controle de acesso</span>
            </div>
        </div>

        <div class="order-1 lg:order-2 relative group">
            <div class="absolute inset-8 bg-indigo-500/20 blur-[70px] rounded-full group-hover:bg-indigo-500/30 transition-colors duration-700"></div>
            <div class="relative rounded-[2.5rem] overflow-hidden border border-white/10 bg-white/[0.03] shadow-2xl p-2">
                <img src="/images/403.gif"
                     alt="Acesso proibido"
                     class="w-full aspect-[4/3] object-cover rounded-[2rem] opacity-90 group-hover:opacity-100 transition-opacity duration-500">
            </div>
        </div>
    </main>
</section>

<script>
    if (window.lucide) {
        lucide.createIcons();
    }
</script>
