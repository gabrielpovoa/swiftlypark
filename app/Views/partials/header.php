<?php
use App\Authorization\Services\NavigationService;
use App\Authorization\Repositories\RbacRepository;
use App\Authorization\Services\RolePermissionResolver;
use App\Context\IdentityContext;
use App\Context\TenantContext;
use App\Repositories\TenantRepository;
use App\Services\AuthorizationService;
use Config\Database;

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
$tenantContext = TenantContext::instance();
$currentCompany = $tenantContext->getCompany();
$currentCompanyId = $tenantContext->getCompanyId();
$connection = (new Database())->connect();
$tenantRepository = new TenantRepository($connection);
$roleSlugs = $identity->roleSlugs();
$globalAuthorization = (new RolePermissionResolver(
    new RbacRepository($connection)
))->resolve($identity->userId(), null);
$globalRoleSlugs = $globalAuthorization->roleSlugs();
$isPlatformAdmin = in_array('master', $globalRoleSlugs, true)
    || in_array('super-admin', $globalRoleSlugs, true);
$supportCompanyId = filter_var(
    $_SESSION['support_impersonation']['company_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if ($isPlatformAdmin && $supportCompanyId !== false && $supportCompanyId !== null) {
    $currentCompanyId = (int) $supportCompanyId;
    $supportCompany = $tenantRepository->findCompanyById((int) $supportCompanyId);

    if ($supportCompany !== null && ($currentCompany === null || $currentCompany->id() !== (int) $supportCompanyId)) {
        $currentCompany = \App\Companies\Domain\Company::reconstitute(
            (int) $supportCompany['id'],
            (string) $supportCompany['name'],
            (string) $supportCompany['slug'],
            $supportCompany['logo_path'] !== null ? (string) $supportCompany['logo_path'] : null,
            true
        );
    }
}
$requestPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
$hasExplicitFinanceFilter = array_key_exists('company_id', $_GET);
$financeCompanyFilter = (string) ($_GET['company_id'] ?? '');
$isGlobalFinanceView = $isPlatformAdmin
    && is_string($requestPath)
    && ($requestPath === '/finance' || str_starts_with($requestPath, '/finance/'))
    && (($hasExplicitFinanceFilter
            && ($financeCompanyFilter === '' || $financeCompanyFilter === 'all'))
        || (!$hasExplicitFinanceFilter
            && ($supportCompanyId === false || $supportCompanyId === null)));
if ($isGlobalFinanceView) {
    $currentCompany = null;
    $currentCompanyId = null;
    $supportCompanyId = null;
}
$tenants = $isPlatformAdmin
    ? $tenantRepository->findSwitchableCompaniesForPlatformUser($identity->userId())
    : $tenantRepository->findCompaniesForUser($identity->userId());
$supportRoles = $isPlatformAdmin ? $tenantRepository->findSupportRoles() : [];
$supportPermissions = $isPlatformAdmin ? $tenantRepository->findActivePermissions() : [];
$supportProfile = is_array($_SESSION['support_impersonation'] ?? null)
    ? $_SESSION['support_impersonation']
    : [];
$supportProfileSelected = ($supportProfile['profile_selected'] ?? false) === true;
$supportRoleSlug = $supportProfileSelected
    ? (string) ($supportProfile['simulated_role'] ?? '')
    : '__super_admin__';
$supportExtraPermissions = is_array($supportProfile['extra_permissions'] ?? null)
    ? array_map('intval', $supportProfile['extra_permissions'])
    : [];
$showTenantSwitcher = count($tenants) > 1
    || $isPlatformAdmin;
$showGlobalPlaceholder = $isPlatformAdmin
    && ($currentCompanyId === null || $currentCompanyId === '')
    && ($supportCompanyId === false || $supportCompanyId === null);
$escape = fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>

<aside class="group fixed top-0 left-0 h-full bg-[#0b0e14] border-r border-white/5 text-slate-400 flex flex-col w-20 hover:w-72 transition-all duration-500 ease-[cubic-bezier(0.4,0,0.2,1)] z-[100] shadow-2xl overflow-hidden">

    <div class="absolute top-0 left-0 w-full h-32 bg-blue-600/5 blur-[50px] pointer-events-none"></div>

    <div class="relative flex items-center h-24 px-6 mb-4 border-b border-white/5 overflow-hidden">
        <div class="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg shadow-blue-500/20 transform group-hover:rotate-12 transition-transform duration-500">
            <span class="text-white font-black italic text-lg select-none">P</span>
        </div>
        <?php if ($currentCompany?->logoPath()): ?>
            <div class="ml-2 flex h-12 w-12 flex-shrink-0 items-center justify-center opacity-0 transition-opacity duration-300 group-hover:opacity-100">
                <img src="/uploads/<?= $escape($currentCompany->logoPath()) ?>"
                     alt="<?= $escape($currentCompany->name()) ?>"
                     class="w-full h-full object-contain">
            </div>
        <?php endif; ?>
        <div class="ml-4 flex min-w-0 flex-1 flex-col opacity-0 group-hover:opacity-100 transition-opacity duration-300">
            <span class="text-white font-black tracking-tighter text-xl italic leading-tight">Swiftly<span class="text-blue-500">Park</span></span>
            <span class="mt-1 max-w-[12rem] text-[9px] uppercase tracking-[0.3em] text-slate-500 font-bold leading-relaxed break-words whitespace-normal">
                <?= $escape($currentCompany?->name() ?? 'Management') ?>
            </span>
        </div>
    </div>

    <nav class="flex-grow flex flex-col gap-2 px-3 overflow-y-auto overflow-x-hidden custom-scrollbar">
        <?php
        $links = $navigation->allowedItems($navigation->defaultItems());

        foreach ($links as $link):
            $href = $link['href'];
            $icon = $link['icon'];
            $label = $link['label'];
            $extraClass = $link['class'] ?? '';
            $id = $link['id'] ?? '';
            ?>
            <a href="<?= $escape($href) ?>" id="<?= $escape($id) ?>"
               class="<?= $extraClass ?> group/item relative flex items-center h-12 rounded-xl hover:bg-white/[0.05] hover:text-white transition-all duration-300">

                <div class="w-14 flex-shrink-0 flex justify-center">
                    <i data-lucide="<?= $escape($icon) ?>"
                       class="w-5 h-5 transition-all duration-300 group-hover/item:text-blue-400 group-hover/item:scale-110"></i>
                </div>

                <span class="opacity-0 group-hover:opacity-100 transition-all duration-300 whitespace-nowrap text-sm font-bold tracking-tight">
                    <?= $escape($label) ?>
                </span>

                <div class="absolute left-0 w-1 h-6 bg-blue-500 rounded-r-full scale-y-0 group-hover/item:scale-y-100 transition-transform origin-center"></div>

                <div class="absolute left-20 px-3 py-2 bg-slate-800 text-white text-[10px] font-bold uppercase tracking-widest rounded-lg shadow-2xl border border-white/5 pointer-events-none opacity-0 group-hover:hidden group-hover/item:opacity-100 transition-all duration-300 translate-x-2 group-hover/item:translate-x-0">
                    <?= $escape($label) ?>
                </div>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="mt-auto px-3 py-6 border-t border-white/5 bg-white/[0.01]">
        <?php if ($showTenantSwitcher): ?>
            <div
                class="tenant-switcher mb-3 rounded-xl border border-white/10 bg-white/[0.03] p-2 opacity-0 group-hover:opacity-100 transition-opacity duration-300"
                data-current-company-id="<?= $escape($currentCompanyId ?? '') ?>">
                <label for="tenant-switcher-select" class="sr-only">Empresa ativa</label>
                <div class="flex items-center gap-2">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-500/10 text-blue-400 border border-blue-500/20">
                        <i data-lucide="building-2" class="h-4 w-4"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <span class="block text-[9px] font-black uppercase tracking-[0.18em] text-slate-500"><?= $isPlatformAdmin ? 'Suporte' : 'Empresa' ?></span>
                        <select
                            id="tenant-switcher-select"
                            class="tenant-switcher__select mt-1 w-full rounded-lg border border-white/10 bg-[#111827] px-2 py-1.5 text-xs font-bold text-white outline-none transition focus:border-blue-500 disabled:opacity-60">
                            <?php if ($isPlatformAdmin): ?>
                                <option value="__global__" <?= $showGlobalPlaceholder ? 'selected' : '' ?>>Dashboard global</option>
                            <?php endif; ?>
                            <?php foreach ($tenants as $tenant): ?>
                                <?php
                                $hasMembership = (bool) ($tenant['has_membership'] ?? true);
                                $roleLabel = trim((string) ($tenant['role_label'] ?? $tenant['role_slug'] ?? ''));
                                $contextLabel = $hasMembership && $roleLabel !== ''
                                    ? $roleLabel
                                    : 'Suporte';
                                ?>
                                <option
                                    value="<?= (int) $tenant['id'] ?>"
                                    <?= (int) $tenant['id'] === (int) $currentCompanyId ? 'selected' : '' ?>>
                                    <?= $escape($tenant['name']) ?> · <?= $escape($contextLabel) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="tenant-switcher__loading hidden h-9 w-9 shrink-0 items-center justify-center rounded-lg text-blue-400" aria-live="polite">
                        <i data-lucide="loader-circle" class="h-4 w-4 animate-spin"></i>
                    </div>
                </div>
            </div>

            <?php if ($isPlatformAdmin && $currentCompanyId !== null && $supportRoles !== []): ?>
                <div
                    class="support-profile mb-3 rounded-xl border border-amber-400/20 bg-amber-400/[0.06] p-2 opacity-0 group-hover:opacity-100 transition-opacity duration-300"
                    data-extra-permissions="<?= $escape(json_encode($supportExtraPermissions, JSON_THROW_ON_ERROR)) ?>">
                    <div class="flex items-start gap-2">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-500/10 text-amber-300 border border-amber-500/20">
                            <i data-lucide="badge-check" class="h-4 w-4"></i>
                        </div>
                        <form class="support-profile__form min-w-0 flex-1">
                            <span class="block text-[9px] font-black uppercase tracking-[0.18em] text-amber-200/80">Visualizar como</span>
                            <select
                                name="simulated_role"
                                class="support-profile__role mt-1 w-full rounded-lg border border-white/10 bg-[#111827] px-2 py-1.5 text-xs font-bold text-white outline-none transition focus:border-amber-400">
                                <option value="__super_admin__" <?= $supportRoleSlug === '__super_admin__' ? 'selected' : '' ?>>
                                    Super-Admin · acesso global
                                </option>
                                <?php foreach ($supportRoles as $role): ?>
                                    <option
                                        value="<?= $escape($role['slug']) ?>"
                                        <?= (string) $role['slug'] === $supportRoleSlug ? 'selected' : '' ?>>
                                        <?= $escape($role['label']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <details class="support-profile__custom mt-2 rounded-lg border border-white/10 bg-black/20 p-2">
                                <summary class="cursor-pointer text-[10px] font-black uppercase tracking-widest text-slate-300">
                                    Permissões extras
                                </summary>
                                <div class="mt-2 max-h-44 space-y-1 overflow-y-auto pr-1">
                                    <?php foreach ($supportPermissions as $permission): ?>
                                        <?php $permissionId = (int) $permission['id']; ?>
                                        <label class="flex items-start gap-2 rounded-md px-1 py-1 text-[11px] font-bold text-slate-300 hover:bg-white/[0.04]">
                                            <input
                                                type="checkbox"
                                                name="extra_permissions[]"
                                                value="<?= $permissionId ?>"
                                                <?= in_array($permissionId, $supportExtraPermissions, true) ? 'checked' : '' ?>
                                                class="mt-0.5 rounded border-white/10 bg-slate-900 text-amber-400 focus:ring-amber-400">
                                            <span class="leading-tight">
                                                <?= $escape($permission['name']) ?>
                                                <span class="block text-[9px] text-slate-500"><?= $escape($permission['slug']) ?></span>
                                            </span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </details>
                            <button class="support-profile__submit mt-2 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-amber-400 px-3 py-2 text-[10px] font-black uppercase tracking-widest text-black hover:bg-amber-300">
                                <i data-lucide="rotate-cw" class="h-3.5 w-3.5"></i>
                                Aplicar perfil
                            </button>
                            <span class="support-profile__loading mt-2 hidden items-center gap-2 text-[10px] font-black uppercase tracking-widest text-amber-200">
                                <i data-lucide="loader-circle" class="h-3.5 w-3.5 animate-spin"></i>
                                Atualizando
                            </span>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

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
    window.SwiftlyParkTenant = Object.assign(window.SwiftlyParkTenant || {}, {
        currentCompanyId: <?= json_encode($currentCompanyId, JSON_THROW_ON_ERROR) ?>,
        currentCompany: <?= json_encode($currentCompany?->toArray(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES) ?>,
        supportImpersonation: <?= json_encode($supportCompanyId !== false && $supportCompanyId !== null, JSON_THROW_ON_ERROR) ?>,
        supportProfile: <?= json_encode($supportProfile, JSON_THROW_ON_ERROR) ?>,
        tenants: <?= json_encode(array_map(
            fn (array $tenant): array => [
                'id' => (int) $tenant['id'],
                'name' => (string) $tenant['name'],
                'slug' => (string) $tenant['slug'],
                'logo_path' => $tenant['logo_path'] !== null ? (string) $tenant['logo_path'] : null,
                'has_membership' => (bool) ($tenant['has_membership'] ?? true),
                'role' => $tenant['role_slug'] !== null ? [
                    'slug' => (string) $tenant['role_slug'],
                    'label' => (string) $tenant['role_label'],
                ] : null,
            ],
            $tenants
        ), JSON_THROW_ON_ERROR) ?>
    });
    lucide.createIcons();
</script>
