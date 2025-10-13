<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$userName = $_SESSION['user_name'] ?? 'Visitante';
?>

<!-- Sidebar moderna -->
<aside class="group fixed top-0 left-0 h-full
    bg-gradient-to-b from-blue-600 via-blue-700 to-blue-900
    text-white flex flex-col w-20 hover:w-64
    transition-all duration-500 z-50 shadow-lg border-r border-blue-400/20 overflow-hidden">

    <!-- Logo -->
    <a href="/" class="flex items-center gap-3 px-5 py-6 border-b border-blue-400/20 select-none">
        <div class="text-3xl font-extrabold font-mono tracking-tight
                    bg-gradient-to-r from-blue-300 to-cyan-400 bg-clip-text text-transparent
                    transition-all duration-500 group-hover:scale-110">
            P
        </div>
        <span class="text-base font-semibold opacity-0 group-hover:opacity-100
                     transition-opacity duration-500 whitespace-nowrap tracking-wide">
            SwiftlyPark
        </span>
    </a>

    <!-- Links principais -->
    <nav class="flex flex-col flex-grow mt-2">
        <?php
        $links = [
            ['/', 'home', 'Início'],
            ['/vacancy/manage', 'layout-grid', 'Gerenciamento de Vagas'],
            ['/About', 'info', 'Sobre'],
            ['/Contact', 'mail', 'Contato'],
            ['#', 'printer', 'Relatório de Log', 'js-print-logs', 'btn-print-logs'],
        ];

        foreach ($links as $link) {
            [$href, $icon, $label] = $link;
            $extraClass = $link[3] ?? '';
            $id = $link[4] ?? '';
            echo "
            <a href='{$href}' id='{$id}' 
               class='{$extraClass} flex items-center gap-4 px-5 py-3
               hover:bg-blue-500/40 transition-all duration-300 relative group/item'>

                <i data-lucide='{$icon}' 
                   class='w-5 h-5 flex-shrink-0 transition-transform duration-300 
                          group-hover/item:scale-110'></i>

                <span class='opacity-0 group-hover:opacity-100 transition-opacity duration-300 whitespace-nowrap'>
                    {$label}
                </span>

                <!-- Tooltip quando recolhido -->
                <span class='absolute left-20 top-1/2 -translate-y-1/2 bg-gray-900/90 text-white text-xs 
                             px-2 py-1 rounded-md shadow-lg ml-2 pointer-events-none opacity-0 
                             group-hover:hidden group-hover/item:opacity-100 transition-opacity duration-300'>
                    {$label}
                </span>
            </a>";
        }
        ?>
    </nav>

    <!-- Separador -->
    <div class="border-t border-blue-400/20 my-3"></div>

    <!-- Perfil / Logout -->
    <div class="flex flex-col pb-5">
        <a href="/Profile"
           class="flex items-center gap-4 px-5 py-3 hover:bg-blue-500/40 transition-all duration-300 group/item">
            <i data-lucide="user" class="w-5 h-5 flex-shrink-0 transition-transform duration-300 group-hover/item:scale-110"></i>
            <span class="uppercase opacity-0 group-hover:opacity-100 transition-opacity duration-300 whitespace-nowrap">
                <?php
                $nameParts = explode(' ', $userName);
                $displayName = implode(' ', array_slice($nameParts, 0, 2));
                echo htmlspecialchars($displayName);
                ?>
            </span>
            <span class="absolute left-20 bg-gray-900/90 text-white text-xs px-2 py-1 rounded-md shadow-lg ml-2 pointer-events-none opacity-0 group-hover:hidden group-hover/item:opacity-100 transition-opacity duration-300">
                Perfil
            </span>
        </a>

        <a href="/login/logout"
           class="flex items-center gap-4 px-5 py-3 hover:bg-red-600/70 transition-all duration-300 group/item">
            <i data-lucide="log-out" class="w-5 h-5 flex-shrink-0 transition-transform duration-300 group-hover/item:scale-110"></i>
            <span class="opacity-0 group-hover:opacity-100 transition-opacity duration-300 whitespace-nowrap">
                Sair
            </span>
            <span class="absolute left-20 bg-gray-900/90 text-white text-xs px-2 py-1 rounded-md shadow-lg ml-2 pointer-events-none opacity-0 group-hover:hidden group-hover/item:opacity-100 transition-opacity duration-300">
                Sair
            </span>
        </a>
    </div>
</aside>

<script>
    lucide.createIcons();
</script>
