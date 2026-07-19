<?php
$escape = static fn (mixed $value): string => htmlspecialchars(
    (string) $value,
    ENT_QUOTES,
    'UTF-8'
);
$filters ??= [
    'query' => '',
    'company_id' => null,
    'status' => 'active',
    'is_filtered' => false,
];
$companies ??= [];
$provisioningCompanies ??= [];
$assignableRoles ??= [];
$canManageIdentity ??= false;
$provisioningFormOpen ??= false;
$provisioningOld ??= [];
$pageUrl = static function (int $targetPage) use ($filters): string {
    $query = array_filter([
        'q' => $filters['query'] ?? '',
        'company_id' => $filters['company_id'] ?? null,
        'status' => ($filters['status'] ?? 'active') === 'active'
            ? null
            : ($filters['status'] ?? 'active'),
        'page' => $targetPage,
    ], static fn (mixed $value): bool => $value !== null && $value !== '');

    return '/identity' . ($query === [] ? '' : '?' . http_build_query($query));
};
?>

<section class="relative min-h-screen overflow-hidden bg-[#080b11] text-slate-300 p-5 md:p-10">
    <div class="pointer-events-none absolute -left-40 top-20 h-96 w-96 rounded-full bg-blue-600/10 blur-[120px]"></div>
    <div class="pointer-events-none absolute -right-40 top-80 h-96 w-96 rounded-full bg-violet-600/10 blur-[120px]"></div>
    <div class="relative mx-auto max-w-7xl">
        <header class="flex flex-col xl:flex-row xl:items-end justify-between gap-5 mb-8">
            <div>
                <a href="/admin" class="mb-5 inline-flex items-center gap-2 text-[10px] font-black uppercase tracking-[0.2em] text-blue-400 hover:text-blue-300">
                    <i data-lucide="arrow-left" class="h-4 w-4"></i>Governança
                </a>
                <span class="block text-blue-400 text-xs font-black uppercase tracking-[0.3em]">Administração Master</span>
                <h1 class="mt-2 text-4xl font-black tracking-tight text-white md:text-5xl">Gestão de Identidade</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-500">Controle quem acessa a plataforma, acompanhe vínculos empresariais e conceda permissões adicionais com rastreabilidade.</p>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="min-w-36 rounded-2xl border border-blue-500/20 bg-blue-500/[0.07] px-5 py-4">
                    <div class="flex items-center justify-between gap-4"><span class="text-[10px] uppercase tracking-widest text-blue-300 font-black">Resultados</span><i data-lucide="users" class="h-4 w-4 text-blue-400"></i></div>
                    <strong class="mt-2 block text-3xl text-white"><?= count($users) ?></strong>
                </div>
                <div class="min-w-36 rounded-2xl border border-violet-500/20 bg-violet-500/[0.07] px-5 py-4">
                    <div class="flex items-center justify-between gap-4"><span class="text-[10px] uppercase tracking-widest text-violet-300 font-black">Página</span><i data-lucide="files" class="h-4 w-4 text-violet-400"></i></div>
                    <strong class="mt-2 block text-3xl text-white"><?= (int) $page ?><span class="text-base text-slate-600">/<?= (int) $totalPages ?></span></strong>
                </div>
            </div>
        </header>

        <?php if ($success): ?>
            <div class="mb-5 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 px-5 py-4 text-emerald-400 font-bold"><?= $escape($success) ?></div>
        <?php elseif ($error): ?>
            <div class="mb-5 rounded-2xl bg-rose-500/10 border border-rose-500/20 px-5 py-4 text-rose-400 font-bold"><?= $escape($error) ?></div>
        <?php endif; ?>

        <?php if ($canManageIdentity): ?>
            <details <?= $provisioningFormOpen ? 'open' : '' ?> class="group mb-6 overflow-hidden rounded-3xl border border-blue-500/20 bg-gradient-to-br from-blue-500/[0.08] to-violet-500/[0.04] shadow-2xl shadow-blue-950/10">
                <summary class="flex cursor-pointer list-none flex-col justify-between gap-4 p-5 md:flex-row md:items-center md:p-6">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-blue-500/20 bg-blue-500/10 text-blue-300">
                            <i data-lucide="user-plus" class="h-6 w-6"></i>
                        </div>
                        <div>
                            <span class="text-[10px] font-black uppercase tracking-[0.22em] text-blue-400">Provisionamento</span>
                            <h2 class="mt-1 text-xl font-black text-white">Adicionar novo usuário</h2>
                            <p class="mt-1 text-sm text-slate-500">Cadastre o acesso e defina a empresa e o perfil inicial.</p>
                        </div>
                    </div>
                    <span class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-3 text-xs font-black text-white shadow-lg shadow-blue-600/20 transition group-hover:bg-blue-500">
                        <span class="group-open:hidden">Novo usuário</span>
                        <span class="hidden group-open:inline">Fechar formulário</span>
                        <i data-lucide="chevron-down" class="h-4 w-4 transition-transform group-open:rotate-180"></i>
                    </span>
                </summary>

                <form method="POST" action="/identity/create" class="grid grid-cols-1 gap-4 border-t border-white/10 bg-black/10 p-5 md:grid-cols-2 md:p-6">
                    <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                    <div>
                        <label for="new-user-name" class="mb-2 block text-[10px] font-black uppercase tracking-widest text-slate-500">Nome completo</label>
                        <input id="new-user-name" name="name" required maxlength="255" autocomplete="name" placeholder="Ana Operadora" value="<?= $escape($provisioningOld['name'] ?? '') ?>"
                               class="w-full rounded-xl border border-white/10 bg-[#090c12] px-4 py-3 text-white outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10">
                    </div>
                    <div>
                        <label for="new-user-email" class="mb-2 block text-[10px] font-black uppercase tracking-widest text-slate-500">E-mail</label>
                        <input id="new-user-email" type="email" name="email" required maxlength="255" autocomplete="email" placeholder="ana@empresa.com" value="<?= $escape($provisioningOld['email'] ?? '') ?>"
                               class="w-full rounded-xl border border-white/10 bg-[#090c12] px-4 py-3 text-white outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10">
                    </div>
                    <div>
                        <label for="new-user-company" class="mb-2 block text-[10px] font-black uppercase tracking-widest text-slate-500">Empresa inicial</label>
                        <select id="new-user-company" name="company_id" required
                                class="w-full rounded-xl border border-white/10 bg-[#090c12] px-4 py-3 text-white outline-none focus:border-blue-500">
                            <option value="">Selecione uma empresa</option>
                            <?php foreach ($provisioningCompanies as $company): ?>
                                <option value="<?= (int) $company['id'] ?>" <?= (int) $company['id'] === (int) ($provisioningOld['company_id'] ?? 0) ? 'selected' : '' ?>><?= $escape($company['name']) ?> · <?= $escape($company['slug']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="new-user-role" class="mb-2 block text-[10px] font-black uppercase tracking-widest text-slate-500">Perfil inicial</label>
                        <select id="new-user-role" name="role_id" required
                                class="w-full rounded-xl border border-white/10 bg-[#090c12] px-4 py-3 text-white outline-none focus:border-blue-500">
                            <option value="">Selecione um perfil</option>
                            <?php foreach ($assignableRoles as $role): ?>
                                <option value="<?= (int) $role['id'] ?>" <?= (int) $role['id'] === (int) ($provisioningOld['role_id'] ?? 0) ? 'selected' : '' ?>><?= $escape($role['label'] ?: $role['name']) ?> · <?= $escape($role['slug']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="md:col-span-2 flex flex-col gap-4 rounded-2xl border border-blue-500/20 bg-blue-500/[0.07] p-4 md:flex-row md:items-center md:justify-between">
                        <p class="flex items-start gap-2 text-sm text-blue-100/80">
                            <i data-lucide="mail-check" class="mt-0.5 h-4 w-4 shrink-0 text-blue-400"></i>
                            Uma senha temporária será gerada, adicionada à fila de e-mail e deverá ser alterada no primeiro acesso.
                        </p>
                        <button class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 py-3 text-sm font-black text-white hover:bg-blue-500">
                            <i data-lucide="user-round-check" class="h-4 w-4"></i>Criar usuário
                        </button>
                    </div>
                </form>
            </details>
        <?php endif; ?>

        <section class="mb-6 rounded-3xl border border-white/10 bg-[#11151e]/90 p-5 shadow-2xl shadow-black/20 backdrop-blur-xl md:p-6">
            <div class="mb-5 flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-blue-500/20 bg-blue-500/10"><i data-lucide="list-filter" class="h-5 w-5 text-blue-400"></i></div>
                <div><h2 class="font-black text-white">Localizar usuários</h2><p class="text-xs text-slate-600">Combine os filtros para encontrar um acesso específico.</p></div>
            </div>
            <form method="GET" action="/identity" class="grid grid-cols-1 lg:grid-cols-[minmax(240px,1fr)_240px_170px_auto_auto] gap-3 items-end">
                <div>
                    <label class="block text-[10px] uppercase tracking-widest text-slate-500 font-black mb-2">Usuário</label>
                    <div class="relative">
                        <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500"></i>
                        <input
                            name="q"
                            value="<?= $escape($filters['query']) ?>"
                            placeholder="buscar por nome ou e-mail"
                            class="w-full rounded-xl bg-[#090c12] border border-white/10 pl-10 pr-4 py-3 text-white outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10">
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] uppercase tracking-widest text-slate-500 font-black mb-2">Empresa</label>
                    <select name="company_id" class="w-full rounded-xl bg-[#090c12] border border-white/10 px-4 py-3 text-white outline-none focus:border-blue-500">
                        <option value="">Todas</option>
                        <?php foreach ($companies as $company): ?>
                            <option
                                value="<?= (int) $company['id'] ?>"
                                <?= (int) $company['id'] === (int) ($filters['company_id'] ?? 0) ? 'selected' : '' ?>>
                                <?= $escape($company['name']) ?><?= $company['deleted_at'] !== null ? ' (inativa)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] uppercase tracking-widest text-slate-500 font-black mb-2">Status</label>
                    <select name="status" class="w-full rounded-xl bg-[#090c12] border border-white/10 px-4 py-3 text-white outline-none focus:border-blue-500">
                        <option value="all" <?= $filters['status'] === 'all' ? 'selected' : '' ?>>Todos</option>
                        <option value="active" <?= $filters['status'] === 'active' ? 'selected' : '' ?>>Ativos</option>
                        <option value="revoked" <?= $filters['status'] === 'revoked' ? 'selected' : '' ?>>Revogados</option>
                    </select>
                </div>

                <button class="inline-flex h-[46px] items-center justify-center gap-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-black px-5 py-3 shadow-lg shadow-blue-600/20">
                    <i data-lucide="search" class="h-4 w-4"></i>Filtrar
                </button>
                <a href="/identity" class="inline-flex h-[46px] items-center justify-center rounded-xl bg-white/[0.05] hover:bg-white/[0.08] text-slate-300 text-center font-black px-5 py-3">
                    Limpar
                </a>
            </form>
        </section>

        <div class="mb-4 flex flex-col justify-between gap-2 sm:flex-row sm:items-end">
            <div>
                <span class="text-[10px] font-black uppercase tracking-[0.22em] text-blue-400">Diretório</span>
                <h2 class="mt-1 text-xl font-black text-white">
                    <?php if ($filters['status'] === 'active'): ?>
                        Usuários ativos
                    <?php elseif ($filters['status'] === 'revoked'): ?>
                        Usuários revogados
                    <?php else: ?>
                        Todos os usuários
                    <?php endif; ?>
                </h2>
            </div>
            <span class="text-xs text-slate-600"><?= count($users) ?> resultado(s) nesta página</span>
        </div>

        <div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-2">
            <?php if ($users === []): ?>
                <div class="rounded-2xl bg-white/[0.025] border border-dashed border-white/10 p-8 text-sm text-slate-500 xl:col-span-2">
                    Nenhum usuário encontrado para os filtros selecionados.
                </div>
            <?php endif; ?>

            <?php foreach ($users as $user):
                $isSelf = (int) $user['id_usuario'] === $currentUserId;
                $isRevoked = $user['deleted_at'] !== null;
                $linkedCompanies = array_filter(array_map(
                    'trim',
                    explode(',', (string) ($user['companies'] ?? ''))
                ));
                $nameParts = preg_split('/\s+/', trim((string) $user['nome'])) ?: [];
                $initials = mb_strtoupper(
                    mb_substr((string) ($nameParts[0] ?? 'U'), 0, 1)
                    . mb_substr((string) ($nameParts[count($nameParts) - 1] ?? ''), 0, 1)
                );
                ?>
                <article class="group overflow-hidden rounded-3xl border border-white/10 bg-[#11151e]/85 shadow-xl shadow-black/10 transition hover:border-blue-500/20 hover:bg-[#131824]">
                    <div class="h-1 bg-gradient-to-r <?= $isRevoked ? 'from-rose-500/70 via-rose-400/20' : 'from-blue-500/70 via-violet-500/30' ?> to-transparent"></div>
                    <div class="p-5 md:p-6">
                    <div class="flex flex-col 2xl:flex-row 2xl:items-center justify-between gap-5">
                        <div class="flex min-w-0 items-start gap-4">
                            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border <?= $isRevoked ? 'border-rose-500/20 bg-rose-500/10 text-rose-300' : 'border-blue-500/20 bg-gradient-to-br from-blue-500/15 to-violet-500/10 text-blue-300' ?> text-lg font-black">
                                <?= $escape($initials) ?>
                            </div>
                            <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="truncate text-lg font-black text-white"><?= $escape($user['nome']) ?></h2>
                                <?php foreach ($user['role_slugs'] as $role): ?>
                                    <span class="px-2 py-1 rounded-lg bg-blue-500/10 text-blue-400 text-[9px] font-black uppercase tracking-widest"><?= $escape($role) ?></span>
                                <?php endforeach; ?>
                                <span class="px-2 py-1 rounded-lg text-[9px] font-black uppercase <?= $isRevoked ? 'bg-rose-500/10 text-rose-400' : 'bg-emerald-500/10 text-emerald-400' ?>">
                                    <?= $isRevoked ? 'Acesso revogado' : 'Ativo' ?>
                                </span>
                            </div>
                            <p class="mt-1 flex items-center gap-2 break-all text-sm text-slate-500"><i data-lucide="mail" class="h-3.5 w-3.5 shrink-0"></i><?= $escape($user['email']) ?></p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <?php if ($linkedCompanies === []): ?>
                                    <span class="rounded-lg bg-white/[0.04] text-slate-500 px-2 py-1 text-[10px] font-black uppercase tracking-widest">sem empresa vinculada</span>
                                <?php endif; ?>
                                <?php foreach ($linkedCompanies as $linkedCompany): ?>
                                    <span class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-500/10 bg-emerald-500/[0.07] text-emerald-400 px-2 py-1 text-[10px] font-black uppercase tracking-widest">
                                        <i data-lucide="building-2" class="h-3 w-3"></i><?= $escape($linkedCompany) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                            </div>
                        </div>

                        <?php if (!$isSelf && !$isRevoked): ?>
                            <form method="POST" action="/identity/revoke" class="w-full 2xl:w-auto"
                                  onsubmit="return confirm('Confirma a revogação deste acesso?');">
                                <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                                <input type="hidden" name="user_id" value="<?= (int) $user['id_usuario'] ?>">
                                <button class="inline-flex w-full items-center justify-center gap-2 px-5 py-3 rounded-xl bg-rose-500/10 hover:bg-rose-500 text-rose-400 hover:text-white border border-rose-500/20 text-xs font-black transition-colors">
                                    <i data-lucide="user-x" class="h-4 w-4"></i>Revogar acesso
                                </button>
                            </form>
                        <?php elseif (!$isSelf && $isRevoked): ?>
                            <form method="POST" action="/identity/reactivate" class="w-full 2xl:w-auto"
                                  onsubmit="return confirm('Reativar este usuário e enviar uma nova senha temporária?');">
                                <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                                <input type="hidden" name="user_id" value="<?= (int) $user['id_usuario'] ?>">
                                <button class="inline-flex w-full items-center justify-center gap-2 px-5 py-3 rounded-xl bg-emerald-500/10 hover:bg-emerald-500 text-emerald-400 hover:text-white border border-emerald-500/20 text-xs font-black transition-colors">
                                    <i data-lucide="user-check" class="h-4 w-4"></i>Reativar
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>

                    <?php if (!$isSelf && !$isRevoked): ?>
                        <details class="mt-5 border-t border-white/5 pt-4">
                            <summary class="flex cursor-pointer list-none items-center justify-between rounded-xl px-3 py-2 text-xs font-black uppercase tracking-widest text-slate-500 hover:bg-white/[0.03] hover:text-white">
                                <span class="flex items-center gap-2"><i data-lucide="shield-check" class="h-4 w-4 text-blue-400"></i>Gerenciar permissões extras</span><i data-lucide="chevron-down" class="h-4 w-4"></i>
                            </summary>
                            <form method="POST" action="/identity/permissions" class="mt-4">
                                <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                                <input type="hidden" name="user_id" value="<?= (int) $user['id_usuario'] ?>">
                                <div class="grid grid-cols-1 2xl:grid-cols-2 gap-3">
                                    <?php foreach ($permissions as $permission): ?>
                                        <?php
                                        $permissionId = (int) $permission['id'];
                                        $isInherited = in_array(
                                            $permissionId,
                                            $user['role_permissions'],
                                            true
                                        );
                                        $isDirect = in_array(
                                            $permissionId,
                                            $user['direct_permissions'],
                                            true
                                        );
                                        $isGranted = $isInherited || $isDirect;
                                        ?>
                                        <label class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition-colors
                                            <?= $isGranted
                                                ? 'bg-blue-500/5 border-blue-500/20'
                                                : 'bg-black/20 border-white/5 hover:border-blue-500/30' ?>">
                                            <input type="checkbox"
                                                <?= !$isInherited ? 'name="permissions[]"' : '' ?>
                                                   value="<?= $permissionId ?>"
                                                <?= $isGranted ? 'checked' : '' ?>
                                                <?= $isInherited ? 'disabled' : '' ?>
                                                   class="mt-1 accent-blue-500 disabled:opacity-60">
                                            <span>
                                                <strong class="block text-xs text-white"><?= $escape($permission['name']) ?></strong>
                                                <span class="text-[10px] text-slate-600"><?= $escape($permission['slug']) ?></span>
                                                <span class="block mt-1 text-[9px] font-black uppercase tracking-wider
                                                    <?= $isGranted ? 'text-blue-400' : 'text-slate-700' ?>">
                                                    <?php if ($isInherited): ?>
                                                        Concedida pelo papel
                                                    <?php elseif ($isDirect): ?>
                                                        Permissão extra
                                                    <?php else: ?>
                                                        Não concedida
                                                    <?php endif; ?>
                                                </span>
                                            </span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <button class="mt-4 px-6 py-3 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-black">
                                    Salvar permissões extras
                                </button>
                            </form>
                        </details>
                    <?php elseif ($isSelf && !$isRevoked): ?>
                        <details class="mt-5 border-t border-white/5 pt-4">
                            <summary class="flex cursor-pointer list-none items-center justify-between rounded-xl px-3 py-2 text-xs font-black uppercase tracking-widest text-slate-500 hover:bg-white/[0.03] hover:text-white">
                                <span class="flex items-center gap-2"><i data-lucide="shield-check" class="h-4 w-4 text-blue-400"></i>Gerenciar minhas permissões extras</span><i data-lucide="chevron-down" class="h-4 w-4"></i>
                            </summary>
                            <form method="POST" action="/identity/permissions" class="mt-4">
                                <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                                <input type="hidden" name="user_id" value="<?= (int) $user['id_usuario'] ?>">
                                <div class="grid grid-cols-1 2xl:grid-cols-2 gap-3">
                                    <?php foreach ($permissions as $permission): ?>
                                        <?php
                                        $permissionId = (int) $permission['id'];
                                        $isInherited = in_array(
                                            $permissionId,
                                            $user['role_permissions'],
                                            true
                                        );
                                        $isDirect = in_array(
                                            $permissionId,
                                            $user['direct_permissions'],
                                            true
                                        );
                                        $isGranted = $isInherited || $isDirect;
                                        ?>
                                        <label class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition-colors
                                            <?= $isGranted
                                                ? 'bg-blue-500/5 border-blue-500/20'
                                                : 'bg-black/20 border-white/5 hover:border-blue-500/30' ?>">
                                            <input type="checkbox"
                                                <?= !$isInherited ? 'name="permissions[]"' : '' ?>
                                                   value="<?= $permissionId ?>"
                                                <?= $isGranted ? 'checked' : '' ?>
                                                <?= $isInherited ? 'disabled' : '' ?>
                                                   class="mt-1 accent-blue-500 disabled:opacity-60">
                                            <span>
                                                <strong class="block text-xs text-white"><?= $escape($permission['name']) ?></strong>
                                                <span class="text-[10px] text-slate-600"><?= $escape($permission['slug']) ?></span>
                                                <span class="block mt-1 text-[9px] font-black uppercase tracking-wider
                                                    <?= $isGranted ? 'text-blue-400' : 'text-slate-700' ?>">
                                                    <?php if ($isInherited): ?>
                                                        Concedida pelo papel
                                                    <?php elseif ($isDirect): ?>
                                                        Permissão extra
                                                    <?php else: ?>
                                                        Não concedida
                                                    <?php endif; ?>
                                                </span>
                                            </span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <button class="mt-4 px-6 py-3 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-black">
                                    Salvar minhas permissões extras
                                </button>
                            </form>
                        </details>
                    <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <nav class="mt-8 flex items-center justify-center gap-3 rounded-2xl border border-white/5 bg-white/[0.02] p-3">
            <?php if ($page > 1): ?>
                <a href="<?= $escape($pageUrl($page - 1)) ?>" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white/5 border border-white/10 hover:bg-white/10"><i data-lucide="chevron-left" class="h-4 w-4"></i>Anterior</a>
            <?php endif; ?>
            <span class="text-xs text-slate-500">Página <?= $page ?> de <?= $totalPages ?></span>
            <?php if ($page < $totalPages): ?>
                <a href="<?= $escape($pageUrl($page + 1)) ?>" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white/5 border border-white/10 hover:bg-white/10">Próxima<i data-lucide="chevron-right" class="h-4 w-4"></i></a>
            <?php endif; ?>
        </nav>
    </div>
</section>
