<?php $this->partial('head', ['title' => $title]); ?>

<section class="relative h-screen w-full bg-[#0b0e14] overflow-hidden flex flex-col items-center justify-center p-6 text-center">

    <div class="absolute top-[-10%] right-[-5%] w-[400px] h-[400px] bg-blue-600/5 blur-[120px] rounded-full pointer-events-none"></div>
    <div class="absolute bottom-[-5%] left-[-5%] w-[300px] h-[300px] bg-rose-600/5 blur-[120px] rounded-full pointer-events-none"></div>

    <div class="relative z-10 flex flex-col items-center max-w-3xl">

        <div class="mb-4 px-4 py-1.5 rounded-full bg-rose-500/10 border border-rose-500/20">
            <span class="text-[10px] font-black uppercase tracking-[0.3em] text-rose-500">Error Code: 404</span>
        </div>

        <h1 class="text-[10rem] md:text-[14rem] font-black leading-none tracking-tighter italic text-transparent bg-clip-text bg-gradient-to-b from-white to-white/10 select-none">
            404
        </h1>

        <div class="relative -mt-10 md:-mt-16 mb-12">
            <h2 class="text-3xl md:text-4xl font-black text-white tracking-tight italic">
                Vaga <span class="text-rose-500">Não Encontrada</span>
            </h2>
            <p class="text-slate-500 mt-4 text-sm font-medium max-w-md mx-auto leading-relaxed">
                Parece que você tentou estacionar em um setor que não existe. O destino foi removido ou nunca esteve aqui.
            </p>
        </div>

        <div class="relative group mb-12">
            <div class="absolute inset-0 bg-blue-600/20 blur-3xl rounded-full scale-75 group-hover:scale-100 transition-transform duration-700"></div>
            <img src="/images/parking.gif" alt="404"
                 class="relative z-10 w-80 md:w-96 rounded-[2.5rem] border border-white/10 grayscale opacity-80 group-hover:grayscale-0 group-hover:opacity-100 transition-all duration-700 shadow-2xl" />
        </div>

        <div class="flex flex-col md:flex-row items-center gap-4">
            <a href="/"
               class="group relative flex items-center gap-3 px-10 py-5 bg-white text-[#0b0e14] rounded-[2rem] font-black text-xs uppercase tracking-widest transition-all hover:bg-blue-500 hover:text-white hover:-translate-y-1 shadow-xl shadow-blue-500/10">
                <i data-lucide="home" class="w-4 h-4 transition-transform group-hover:-rotate-12"></i>
                <span>Voltar ao Início</span>
            </a>

            <button onclick="history.back()"
                    class="px-10 py-5 bg-white/5 border border-white/10 text-white rounded-[2rem] font-black text-xs uppercase tracking-widest hover:bg-white/10 transition-all active:scale-95">
                Página Anterior
            </button>
        </div>
    </div>

    <div class="absolute bottom-8 left-0 w-full flex justify-center opacity-20">
        <p class="text-[10px] font-bold uppercase tracking-[0.5em] text-slate-400">SwiftlyPark Monitoring System</p>
    </div>

</section>

<script>
    // Inicializa os ícones Lucide se estiverem presentes
    if (window.lucide) {
        lucide.createIcons();
    }
</script>