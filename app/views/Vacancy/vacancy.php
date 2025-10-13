<?php $this->partial('head', ['title' => $title]); ?>

<section class="min-h-screen p-8 bg-gradient-to-br from-[#1e2a47] to-[#121826] flex flex-col items-center">

    <h1 class="text-3xl md:text-4xl font-bold text-white mb-10 text-center">Vagas Disponíveis</h1>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 w-full max-w-6xl">

        <!-- Card Carro -->
        <a href="/vacancy/apply?type=carro"
           class="relative block bg-[#1f2b4a] rounded-2xl p-6 shadow-lg flex flex-col items-center
                  hover:bg-[#27457a] transition duration-500 transform hover:scale-105 hover:-translate-y-1
                  hover:shadow-2xl group">
            <div class="bg-gradient-to-tr from-[#2a3a5a] to-[#1e2b4a] p-4 rounded-full mb-4">
                <img src="/images/car.png" alt="Carros" class="w-20 h-20 object-contain" />
            </div>
            <h2 class="text-xl font-semibold mb-2 text-white group-hover:text-blue-400 transition-colors text-center"><?= "Carros" ?></h2>
            <p class="text-4xl font-bold text-white"><?= $counts['carro'] ?? 0 ?></p>
            <p class="mt-1 text-gray-300">vagas disponíveis</p>
        </a>

        <!-- Card Moto -->
        <a href="/vacancy/apply?type=moto"
           class="relative block bg-[#1f2b4a] rounded-2xl p-6 shadow-lg flex flex-col items-center
                  hover:bg-[#27457a] transition duration-500 transform hover:scale-105 hover:-translate-y-1
                  hover:shadow-2xl group">
            <div class="bg-gradient-to-tr from-[#2a3a5a] to-[#1e2b4a] p-4 rounded-full mb-4">
                <img src="/images/moto.png" alt="Motos" class="w-20 h-20 object-contain" />
            </div>
            <h2 class="text-xl font-semibold mb-2 text-white group-hover:text-blue-400 transition-colors text-center">Motos</h2>
            <p class="text-4xl font-bold text-white"><?= $counts['moto'] ?? 0 ?></p>
            <p class="mt-1 text-gray-300">vagas disponíveis</p>
        </a>

        <!-- Card Caminhão -->
        <a href="/vacancy/apply?type=caminhao"
           class="relative block bg-[#1f2b4a] rounded-2xl p-6 shadow-lg flex flex-col items-center
                  hover:bg-[#27457a] transition duration-500 transform hover:scale-105 hover:-translate-y-1
                  hover:shadow-2xl group">
            <div class="bg-gradient-to-tr from-[#2a3a5a] to-[#1e2b4a] p-4 rounded-full mb-4">
                <img src="/images/truck.png" alt="Caminhão" class="w-20 h-20 object-contain" />
            </div>
            <h2 class="text-xl font-semibold mb-2 text-white group-hover:text-blue-400 transition-colors text-center">Caminhão</h2>
            <p class="text-4xl font-bold text-white"><?= $counts['caminhao'] ?? 0 ?></p>
            <p class="mt-1 text-gray-300">vagas disponíveis</p>
        </a>

        <!-- Card Apps -->
        <a href="/vacancy/apply?type=app"
           class="relative block bg-[#1f2b4a] rounded-2xl p-6 shadow-lg flex flex-col items-center
                  hover:bg-[#27457a] transition duration-500 transform hover:scale-105 hover:-translate-y-1
                  hover:shadow-2xl group">
            <div class="bg-gradient-to-tr from-[#2a3a5a] to-[#1e2b4a] p-4 rounded-full mb-4">
                <img src="/images/uber.png" alt="Apps" class="w-20 h-20 object-contain" />
            </div>
            <h2 class="text-xl font-semibold mb-2 text-white group-hover:text-blue-400 transition-colors text-center">Apps (Uber, 99Pop)</h2>
            <p class="text-4xl font-bold text-white"><?= $counts['app'] ?? 0 ?></p>
            <p class="mt-1 text-gray-300">vagas disponíveis</p>
        </a>
    </div>

    <!-- Ações -->
    <div class="flex flex-wrap justify-center mt-12 gap-4 w-full max-w-3xl">
        <a href="vacancy/manage"
           class="flex items-center gap-2 px-6 py-3 rounded-xl bg-[#2B4570] hover:bg-white hover:text-[#2B4570]
                  text-white font-semibold transition-colors duration-500 shadow-lg hover:shadow-xl">
            <i data-lucide="layout-grid" class="w-5 h-5"></i> Gerenciar Vagas
        </a>

        <a href="/CreateVacancy"
           class="flex items-center gap-2 px-6 py-3 rounded-xl bg-[#2B4570] hover:bg-white hover:text-[#2B4570]
                  text-white font-semibold transition-colors duration-500 shadow-lg hover:shadow-xl">
            <i data-lucide="plus" class="w-5 h-5"></i> Adicionar Novas Vagas
        </a>
    </div>

</section>
