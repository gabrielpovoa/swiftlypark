<?php $this->partial('head', ['title' => $title]); ?>

<section class="p-8 min-h-screen text-white font-sans">
    <h1 class="text-3xl font-bold mb-8">Vagas Disponíveis</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">

        <a href="/vacancy/apply?type=carro"
           class="block bg-[#1E3050] rounded-lg p-6 shadow-lg flex flex-col items-center
                  hover:bg-[#27457a] transition-colors duration-300
                  transform transition-transform duration-500 ease-out
                  hover:scale-105 hover:-translate-y-1 hover:shadow-2xl">
            <img src="/images/car.png" alt="Carros" class="w-20 h-20 object-contain mb-4" />
            <h2 class="text-xl font-semibold mb-2">Carros</h2>
            <p class="text-4xl font-bold"><?=$counts['carro'] ?? 0 ?></p>
            <p class="mt-1 text-gray-300">vagas disponíveis</p>
        </a>

        <a href="/vacancy/apply?type=moto"
           class="block bg-[#1E3050] rounded-lg p-6 shadow-lg flex flex-col items-center
                  hover:bg-[#27457a] transition-colors duration-300
                  transform transition-transform duration-500 ease-out
                  hover:scale-105 hover:-translate-y-1 hover:shadow-2xl">
            <img src="/images/moto.png" alt="Motos" class="w-20 h-20 object-contain mb-4" />
            <h2 class="text-xl font-semibold mb-2">Motos</h2>
            <p class="text-4xl font-bold"><?=$counts['moto'] ?? 0 ?></p>
            <p class="mt-1 text-gray-300">vagas disponíveis</p>
        </a>

        <a href="/vacancy/apply?type=caminhao"
           class="block bg-[#1E3050] rounded-lg p-6 shadow-lg flex flex-col items-center
                  hover:bg-[#27457a] transition-colors duration-300
                  transform transition-transform duration-500 ease-out
                  hover:scale-105 hover:-translate-y-1 hover:shadow-2xl">
            <img src="/images/truck.png" alt="Caminhão" class="w-20 h-20 object-contain mb-4" />
            <h2 class="text-xl font-semibold mb-2">Caminhão</h2>
            <p class="text-4xl font-bold"><?=$counts['caminhao'] ?? 0 ?></p>
            <p class="mt-1 text-gray-300">vagas disponíveis</p>
        </a>

        <a href="/vacancy/apply?type=app"
           class="block bg-[#1E3050] rounded-lg p-6 shadow-lg flex flex-col items-center
                  hover:bg-[#27457a] transition-colors duration-300
                  transform transition-transform duration-500 ease-out
                  hover:scale-105 hover:-translate-y-1 hover:shadow-2xl">
            <img src="/images/uber.png" alt="Motoristas de aplicativos: Uber, 99pop e Indrive" class="w-20 h-20 object-contain mb-4" />
            <h2 class="text-xl font-semibold mb-2">Apps (Uber, 99Pop)</h2>
            <p class="text-4xl font-bold"><?=$counts['app'] ?? 0 ?></p>
            <p class="mt-1 text-gray-300">vagas disponíveis</p>
        </a>

    </div>
</section>
