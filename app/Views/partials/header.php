<?php
use App\Authorization\Services\NavigationService;
use App\Context\IdentityContext;
use App\Services\AuthorizationService;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$userName  = $_SESSION['user_name'] ?? 'Visitante';
$userPhoto = $_SESSION['user_photo'] ?? null;
$photoPath = __DIR__ . '/../../public/uploads/' . $userPhoto;
$photoUrl  = '/uploads/' . $userPhoto;
$identity = IdentityContext::current();
$roleMetadata = $identity->roleMetadata();
$navigation = new NavigationService(new AuthorizationService($identity));
?>

<aside class="group fixed top-0 left-0 h-full bg-[#0b0e14] border-r border-white/5 text-slate-400 flex flex-col w-20 hover:w-72 transition-all duration-500 ease-[cubic-bezier(0.4,0,0.2,1)] z-[100] shadow-2xl overflow-hidden">

    <div class="absolute top-0 left-0 w-full h-32 bg-blue-600/5 blur-[50px] pointer-events-none"></div>

    <div class="relative flex items-center h-24 px-6 mb-4 border-b border-white/5 overflow-hidden">
        <div class="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg shadow-blue-500/20 transform group-hover:rotate-12 transition-transform duration-500">
            <span class="text-white font-black italic text-lg select-none">P</span>
        </div>
        <div class="ml-4 flex flex-col opacity-0 group-hover:opacity-100 transition-opacity duration-300 whitespace-nowrap">
            <span class="text-white font-black tracking-tighter text-xl italic">Swiftly<span class="text-blue-500">Park</span></span>
            <span class="text-[9px] uppercase tracking-[0.3em] text-slate-500 font-bold -mt-1">Management</span>
        </div>
    </div>

    <nav class="flex-grow flex flex-col gap-2 px-3 overflow-y-auto overflow-x-hidden custom-scrollbar">
        <?php
        $links = $navigation->allowedItems([
            ['href' => '/', 'icon' => 'home', 'label' => 'Painel Inicial', 'permission' => 'dashboard.view'],
            ['href' => '/vacancy/manage', 'icon' => 'layout-grid', 'label' => 'Gerenciar Vagas', 'permission' => 'vehicle.view'],
            ['href' => '/About', 'icon' => 'info', 'label' => 'Sobre o Projeto'],
            ['href' => '/Contact', 'icon' => 'mail', 'label' => 'Suporte & Contato'],
            ['href' => '#', 'icon' => 'printer', 'label' => 'Relatórios', 'permission' => 'report.view', 'class' => 'js-print-logs', 'id' => 'btn-print-logs'],
            ['href' => '/audit', 'icon' => 'search-check', 'label' => 'Auditoria', 'permission' => 'audit.view'],
            ['href' => '/identity', 'icon' => 'users-round', 'label' => 'Usuários', 'permission' => 'identity.view'],
            ['href' => '/finance', 'icon' => 'chart-column', 'label' => 'Relatórios Financeiros', 'permission' => 'finance.view'],
        ]);

        foreach ($links as $link):
            $href = $link['href'];
            $icon = $link['icon'];
            $label = $link['label'];
            $extraClass = $link['class'] ?? '';
            $id = $link['id'] ?? '';
            ?>
            <a href="<?= $href ?>" id="<?= $id ?>"
               class="<?= $extraClass ?> group/item relative flex items-center h-12 rounded-xl hover:bg-white/[0.05] hover:text-white transition-all duration-300">

                <div class="w-14 flex-shrink-0 flex justify-center">
                    <i data-lucide="<?= $icon ?>"
                       class="w-5 h-5 transition-all duration-300 group-hover/item:text-blue-400 group-hover/item:scale-110"></i>
                </div>

                <span class="opacity-0 group-hover:opacity-100 transition-all duration-300 whitespace-nowrap text-sm font-bold tracking-tight">
                    <?= $label ?>
                </span>

                <div class="absolute left-0 w-1 h-6 bg-blue-500 rounded-r-full scale-y-0 group-hover/item:scale-y-100 transition-transform origin-center"></div>

                <div class="absolute left-20 px-3 py-2 bg-slate-800 text-white text-[10px] font-bold uppercase tracking-widest rounded-lg shadow-2xl border border-white/5 pointer-events-none opacity-0 group-hover:hidden group-hover/item:opacity-100 transition-all duration-300 translate-x-2 group-hover/item:translate-x-0">
                    <?= $label ?>
                </div>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="mt-auto px-3 py-6 border-t border-white/5 bg-white/[0.01]">

        <a href="/Profile" class="group/user flex items-center h-14 rounded-2xl hover:bg-white/[0.05] transition-all duration-300 mb-2">
            <div class="w-14 flex-shrink-0 flex justify-center">
                <?php
                // 1. Caminho absoluto no disco para checagem real do arquivo
                $photoDiskPath = $_SERVER['DOCUMENT_ROOT'] . '/uploads/' . $userPhoto;

                // 2. Só exibe a imagem se o nome existir no banco E o arquivo existir na pasta
                if ($userPhoto && file_exists($photoDiskPath)): ?>
                    <div class="w-9 h-9 rounded-full overflow-hidden border-2 border-blue-500/20 group-hover/user:border-blue-500 transition-all duration-300 p-0.5">
                        <img src="/uploads/<?= htmlspecialchars($userPhoto) ?>"
                             class="w-full h-full rounded-full object-cover">
                    </div>
                <?php else: ?>
                    <div class="w-9 h-9 rounded-full bg-blue-500/10 flex items-center justify-center text-blue-500 border border-blue-500/20 group-hover/user:bg-blue-500/20 transition-all">
                        <i data-lucide="user" class="w-4 h-4"></i>
                    </div>
                <?php endif; ?>
            </div>

            <div class="opacity-0 group-hover:opacity-100 transition-all duration-300 overflow-hidden ml-1">
            <span class="block text-[11px] font-black text-white truncate uppercase tracking-tighter">
                <?= htmlspecialchars(implode(' ', array_slice(explode(' ', $userName), 0, 2))) ?>
            </span>
                <?php $this->partial('role-badge', ['roleMetadata' => $roleMetadata]); ?>
            </div>
        </a>

        <a href="/login/logout" class="group/logout flex items-center h-12 rounded-xl text-rose-500/70 hover:bg-rose-500/10 hover:text-rose-500 transition-all duration-300">
            <div class="w-14 flex-shrink-0 flex justify-center">
                <i data-lucide="log-out" class="w-5 h-5"></i>
            </div>
            <span class="opacity-0 group-hover:opacity-100 transition-all duration-300 text-sm font-bold">Sair do Sistema</span>
        </a>
    </div>
</aside>

<style>
    /* Scrollbar minimalista para o menu caso cresça muito */
    .custom-scrollbar::-webkit-scrollbar { width: 0px; }
    .group:hover .custom-scrollbar::-webkit-scrollbar { width: 2px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(59, 130, 246, 0.2); border-radius: 10px; }
</style>

<script>
    lucide.createIcons();
</script>
