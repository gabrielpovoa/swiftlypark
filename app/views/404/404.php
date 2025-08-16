<?php $this->partial('head', ['title' => $title]); ?>

<section class="flex flex-col items-center justify-center min-h-screen gap-3 bg-gray-100 p-6">
    <h1 class="text-6xl font-extrabold text-red-600 mb-2">404</h1>
    <h2 class="text-3xl font-semibold text-gray-800 mb-6">Página não encontrada</h2>

    <img src="/images/parking.gif" alt="Página não encontrada" class="pointer-events-none w-96 max-w-full rounded-lg shadow-lg" />

    <div class="text-center mt-4">
        <p class="mb-3 text-gray-700 text-lg">Ops! A página que você está procurando não existe.</p>
        <a href="/" class="inline-block px-6 py-3 bg-blue-600 text-white font-semibold rounded-md hover:bg-blue-700 transition">
            Voltar para a página inicial
        </a>
    </div>
</section>
