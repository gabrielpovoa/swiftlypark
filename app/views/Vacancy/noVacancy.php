<?php $this->partial('head', ['title' => $title]); ?>

<section class="min-h-screen flex items-center justify-center bg-gradient-to-b from-gray-950 to-gray-900 text-white p-8">

    <div class="w-full max-w-4xl text-center bg-white/5 backdrop-blur-2xl border border-white/10 shadow-2xl 
                rounded-md p-16 flex flex-col items-center">

        <!-- Ícone grande -->
        <div class="flex justify-center mb-10">
            <div class="w-32 h-32 rounded-md bg-gradient-to-br from-blue-600 to-indigo-700 
                        flex items-center justify-center shadow-[0_0_40px_rgba(0,150,255,0.5)]">
                <i data-lucide="parking-square" class="w-20 h-20 text-white"></i>
            </div>
        </div>

        <!-- Título principal -->
        <h1 class="text-5xl font-bold tracking-wide bg-gradient-to-r from-blue-400 to-indigo-400 
                   bg-clip-text text-transparent drop-shadow-md">
            Nenhuma Vaga Disponível
        </h1>

        <!-- Subtítulo -->
        <p class="mt-6 text-gray-300 text-lg leading-relaxed max-w-2xl">
            Todas as vagas da categoria 
            <span class="text-blue-400 font-semibold"><?= htmlspecialchars($type) ?></span> 
            estão ocupadas no momento.
        </p>

        <p class="mt-2 text-gray-400 text-sm max-w-xl">
            Acompanhe em tempo real — assim que uma vaga for liberada, o sistema permitirá o cadastro imediatamente.
        </p>

        <!-- Botão robusto -->
        <a href="/CreateVacancy"
           class="mt-12 inline-block px-12 py-4 rounded-md bg-gradient-to-r from-blue-600 to-indigo-600 
                  hover:from-blue-700 hover:to-indigo-700 text-lg font-semibold shadow-xl 
                  transition-all hover:scale-105 tracking-wide">
            Crei Novas Vagas
        </a>

    </div>

</section>
