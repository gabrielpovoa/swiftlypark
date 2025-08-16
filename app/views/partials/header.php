<div class="group relative">

    <aside class="group fixed top-0 left-0 h-full bg-blue-600 text-white flex flex-col w-14 hover:w-64 transition-all duration-300 z-50">

        <!-- Logo P + Nome lado a lado -->
        <a href="/" class="cursor-pointer flex items-center gap-2 px-4 py-4 border-b border-blue-500 cursor-default select-none">

            <!-- Letra P -->
            <div class="text-5xl font-bold font-mono transition-all duration-300 group-hover:text-7xl">
                P
            </div>

            <!-- Nome SwiftlyPark, aparece só no hover -->
            <span class="text-sm font-semibold opacity-0 pointer-events-none transition-opacity duration-300 group-hover:opacity-100 group-hover:pointer-events-auto select-none whitespace-nowrap">
                SwiftlyPark
            </span>
        </a>

        <a href="/" class="flex items-center gap-3 px-4 group-hover:px-4 py-3 hover:bg-blue-700 transition-all duration-300">
            <i data-lucide="home" class="min-w-[24px] mx-auto group-hover:mx-0"></i>
            <span class="opacity-0 group-hover:opacity-100 transition-opacity duration-200 whitespace-nowrap">
                Início
            </span>
        </a>

        <a href="/vacancy/manage" class="flex items-center gap-3 px-4 group-hover:px-4 py-3 hover:bg-blue-700 transition-all duration-300">
            <i data-lucide="layout-grid" class="min-w-[24px] mx-auto group-hover:mx-0"></i>
            <span class="opacity-0 group-hover:opacity-100 transition-opacity duration-200 whitespace-nowrap">
                Gerenciamento de Vagas
            </span>
        </a>

        <a href="/About" class="flex items-center gap-3 px-4 group-hover:px-4 py-3 hover:bg-blue-700 transition-all duration-300">
            <i data-lucide="info" class="min-w-[24px] mx-auto group-hover:mx-0"></i>
            <span class="opacity-0 group-hover:opacity-100 transition-opacity duration-200 whitespace-nowrap">
                Sobre
            </span>
        </a>

        <a href="/Contact" class="flex items-center gap-3 px-4 group-hover:px-4 py-3 hover:bg-blue-700 transition-all duration-300">
            <i data-lucide="mail" class="min-w-[24px] mx-auto group-hover:mx-0"></i>
            <span class="opacity-0 group-hover:opacity-100 transition-opacity duration-200 whitespace-nowrap">
                Contato
            </span>
        </a>

        <a href="#" id="btn-print-logs" class="js-print-logs flex items-center gap-3 px-4 group-hover:px-4 py-3 hover:bg-blue-700 transition-all duration-300">
            <i data-lucide="printer" class="min-w-[24px] mx-auto group-hover:mx-0"></i>
            <span class="opacity-0 group-hover:opacity-100 transition-opacity duration-200 whitespace-nowrap">
                Relatório de Log
            </span>
        </a>


        <!-- Espaço automático para empurrar o user para baixo -->
        <div class="mt-auto">
            <a href="Profile" class="flex items-center gap-3 px-4 group-hover:px-4 py-3 hover:bg-blue-700 transition-all duration-300">
                <i data-lucide="user" class="min-w-[24px] mx-auto group-hover:mx-0"></i>
                <span class="opacity-0 group-hover:opacity-100 transition-opacity duration-200 whitespace-nowrap">
                    User Logged
                </span>
            </a>
            <a href="/contato" class="flex items-center gap-3 px-4 group-hover:px-4 py-3 hover:bg-blue-700 transition-all duration-300">
                <i data-lucide="log-out" class="min-w-[24px] mx-auto group-hover:mx-0"></i>
                <span class="opacity-0 group-hover:opacity-100 transition-opacity duration-200 whitespace-nowrap">
                    Sair
                </span>
            </a>
        </div>

    </aside>

</div>
