<?php $this->partial('head', ['title' => $title]); ?>

<section class="flex items-center justify-center min-h-screen bg-gradient-to-br from-[#1e2a47] to-[#121826] px-6 py-10">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-10 w-full max-w-5xl">

        <!-- Card Sobre -->
        <div class="bg-[#1f2b4a] shadow-lg rounded-2xl px-8 py-10 flex flex-col gap-6 relative overflow-hidden">
            <h2 class="text-3xl md:text-4xl font-semibold text-center text-white">📖 Sobre o Projeto</h2>

            <p class="text-lg md:text-xl leading-relaxed text-gray-200">
                O <span class="font-semibold text-blue-400">SwiftlyPark</span> é um sistema moderno criado para
                facilitar a gestão e utilização de estacionamentos, oferecendo uma interface <strong>simples, intuitiva e responsiva</strong>.
            </p>

            <p class="text-lg md:text-xl leading-relaxed text-gray-200">
                Com funcionalidades como <span class="font-semibold text-blue-300">monitoramento de vagas</span>,
                <span class="font-semibold text-blue-300">controle de acesso</span>, emissão de tickets e
                <span class="font-semibold text-blue-300">relatórios detalhados</span>, ele proporciona mais praticidade
                tanto para administradores quanto para usuários.
            </p>

            <p class="text-lg md:text-xl leading-relaxed text-gray-200">
                Desenvolvido com <span class="font-semibold text-blue-400">PHP</span> e
                <span class="font-semibold text-blue-400">TailwindCSS</span>, seguindo práticas modernas
                de desenvolvimento para garantir <strong>desempenho eficiente</strong> e uma
                <strong>experiência de uso premium</strong>.
            </p>

            <!-- Call-to-Action -->
            <div class="mt-6 flex justify-center">
                <a href="/landingPage"
                   class="px-8 py-3 bg-blue-600 hover:bg-blue-700 rounded-xl font-semibold text-white shadow-lg transition-all duration-300 text-lg flex items-center gap-2">
                    <i data-lucide="info"></i> Conheça o Sistema
                </a>
            </div>
        </div>

        <!-- Card Extra / Destaque visual -->
        <div class="flex flex-col items-center justify-center text-center px-6">
            <div class="w-72 bg-[#0A2463] shadow-lg rounded-2xl px-6 py-8 text-[#DDDBF1] animate-fadeIn">
                <i data-lucide="layers" class="w-10 h-10 mb-3 text-yellow-400"></i>
                <h3 class="text-lg font-semibold mb-2">Funcionalidades</h3>
                <p class="text-sm opacity-80 mb-2">Monitoramento de vagas, controle de acesso, emissão de tickets, relatórios e muito mais.</p>
                <p class="text-sm opacity-70">Tudo pensado para oferecer uma experiência intuitiva e moderna.</p>
            </div>
        </div>

    </div>
</section>
