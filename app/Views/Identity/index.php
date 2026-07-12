<?php
$escape = static fn (mixed $value): string => htmlspecialchars(
    (string) $value,
    ENT_QUOTES,
    'UTF-8'
);
$filters ??= [
    'query' => '',
    'company_id' => null,
    'status' => 'all',
    'is_filtered' => false,
];
$companies ??= [];
$pageUrl = static function (int $targetPage) use ($filters): string {
    $query = array_filter([
        'q' => $filters['query'] ?? '',
        'company_id' => $filters['company_id'] ?? null,
        'status' => ($filters['status'] ?? 'all') === 'all'
            ? null
            : ($filters['status'] ?? 'all'),
        'page' => $targetPage,
    ], static fn (mixed $value): bool => $value !== null && $value !== '');

    return '/identity' . ($query === [] ? '' : '?' . http_build_query($query));
};
?>

<section class="min-h-screen bg-[#0b0e14] text-slate-300 p-5 md:p-10">
    <div class="max-w-7xl mx-auto">
        <header class="flex flex-col xl:flex-row xl:items-end justify-between gap-5 mb-8">
            <div>
                <span class="text-blue-500 text-xs font-black uppercase tracking-[0.3em]">Administração Master</span>
                <h1 class="text-4xl font-black text-white mt-2">Gestão de Identidade</h1>
                <p class="text-slate-500 mt-2">Revogue acessos, encontre usuários e conceda permissões adicionais sem alterar o papel principal.</p>
            </div>
            <div class="grid grid-cols-2 gap-3 text-right">
                <div class="rounded-2xl bg-white/[0.03] border border-white/10 px-5 py-4">
                    <span class="block text-[10px] uppercase tracking-widest text-slate-500 font-black">Na lista</span>
                    <strong class="text-2xl text-white"><?= count($users) ?></strong>
                </div>
                <div class="rounded-2xl bg-white/[0.03] border border-white/10 px-5 py-4">
                    <span class="block text-[10px] uppercase tracking-widest text-slate-500 font-black">Página</span>
                    <strong class="text-2xl text-white"><?= (int) $page ?>/<?= (int) $totalPages ?></strong>
                </div>
            </div>
        </header>

        <?php if ($success): ?>
            <div class="mb-5 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 px-5 py-4 text-emerald-400 font-bold"><?= $escape($success) ?></div>
        <?php elseif ($error): ?>
            <div class="mb-5 rounded-2xl bg-rose-500/10 border border-rose-500/20 px-5 py-4 text-rose-400 font-bold"><?= $escape($error) ?></div>
        <?php endif; ?>

        <section class="rounded-2xl border border-white/10 bg-white/[0.025] p-5 md:p-6 mb-6">
            <form method="GET" action="/identity" class="grid grid-cols-1 lg:grid-cols-[minmax(240px,1fr)_240px_170px_auto_auto] gap-3 items-end">
                <div>
                    <label class="block text-[10px] uppercase tracking-widest text-slate-500 font-black mb-2">Usuário</label>
                    <div class="relative">
                        <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500"></i>
                        <input
                            name="q"
                            value="<?= $escape($filters['query']) ?>"
                            placeholder="buscar por nome ou e-mail"
                            class="w-full rounded-xl bg-[#131720] border border-white/10 pl-10 pr-4 py-3 text-white outline-none focus:border-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] uppercase tracking-widest text-slate-500 font-black mb-2">Empresa</label>
                    <select name="company_id" class="w-full rounded-xl bg-[#131720] border border-white/10 px-4 py-3 text-white outline-none focus:border-blue-500">
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
                    <select name="status" class="w-full rounded-xl bg-[#131720] border border-white/10 px-4 py-3 text-white outline-none focus:border-blue-500">
                        <option value="all" <?= $filters['status'] === 'all' ? 'selected' : '' ?>>Todos</option>
                        <option value="active" <?= $filters['status'] === 'active' ? 'selected' : '' ?>>Ativos</option>
                        <option value="revoked" <?= $filters['status'] === 'revoked' ? 'selected' : '' ?>>Revogados</option>
                    </select>
                </div>

                <button class="h-[46px] rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-black px-5 py-3">
                    Filtrar
                </button>
                <a href="/identity" class="inline-flex h-[46px] items-center justify-center rounded-xl bg-white/[0.05] hover:bg-white/[0.08] text-slate-300 text-center font-black px-5 py-3">
                    Limpar
                </a>
            </form>
        </section>

        <div class="space-y-4">
            <?php if ($users === []): ?>
                <div class="rounded-2xl bg-white/[0.025] border border-dashed border-white/10 p-8 text-sm text-slate-500">
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
                ?>
                <article class="rounded-3xl border border-white/10 bg-white/[0.025] p-5 md:p-6">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-5">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-lg font-black text-white"><?= $escape($user['nome']) ?></h2>
                                <?php foreach ($user['role_slugs'] as $role): ?>
                                    <span class="px-2 py-1 rounded-lg bg-blue-500/10 text-blue-400 text-[9px] font-black uppercase tracking-widest"><?= $escape($role) ?></span>
                                <?php endforeach; ?>
                                <span class="px-2 py-1 rounded-lg text-[9px] font-black uppercase <?= $isRevoked ? 'bg-rose-500/10 text-rose-400' : 'bg-emerald-500/10 text-emerald-400' ?>">
                                    <?= $isRevoked ? 'Acesso revogado' : 'Ativo' ?>
                                </span>
                            </div>
                            <p class="text-sm text-slate-500 mt-1"><?= $escape($user['email']) ?></p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <?php if ($linkedCompanies === []): ?>
                                    <span class="rounded-lg bg-white/[0.04] text-slate-500 px-2 py-1 text-[10px] font-black uppercase tracking-widest">sem empresa vinculada</span>
                                <?php endif; ?>
                                <?php foreach ($linkedCompanies as $linkedCompany): ?>
                                    <span class="rounded-lg bg-emerald-500/10 text-emerald-400 px-2 py-1 text-[10px] font-black uppercase tracking-widest">
                                        <?= $escape($linkedCompany) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <?php if (!$isSelf && !$isRevoked): ?>
                            <form method="POST" action="/identity/revoke"
                                  onsubmit="return confirm('Confirma a revogação deste acesso?');">
                                <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                                <input type="hidden" name="user_id" value="<?= (int) $user['id_usuario'] ?>">
                                <button class="px-5 py-3 rounded-xl bg-rose-500/10 hover:bg-rose-500 text-rose-400 hover:text-white border border-rose-500/20 text-xs font-black transition-colors">
                                    Revogar acesso
                                </button>
                            </form>
                        <?php elseif (!$isSelf && $isRevoked): ?>
                            <form method="POST" action="/identity/reactivate"
                                  onsubmit="return confirm('Reativar este usuário e enviar uma nova senha temporária?');">
                                <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                                <input type="hidden" name="user_id" value="<?= (int) $user['id_usuario'] ?>">
                                <button class="px-5 py-3 rounded-xl bg-emerald-500/10 hover:bg-emerald-500 text-emerald-400 hover:text-white border border-emerald-500/20 text-xs font-black transition-colors">
                                    Reativar
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>

                    <?php if (!$isSelf && !$isRevoked): ?>
                        <details class="mt-5 border-t border-white/5 pt-4">
                            <summary class="cursor-pointer text-xs font-black uppercase tracking-widest text-slate-500 hover:text-white">
                                Gerenciar permissões extras
                            </summary>
                            <form method="POST" action="/identity/permissions" class="mt-4">
                                <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                                <input type="hidden" name="user_id" value="<?= (int) $user['id_usuario'] ?>">
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
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
                            <summary class="cursor-pointer text-xs font-black uppercase tracking-widest text-slate-500 hover:text-white">
                                Gerenciar minhas permissões extras
                            </summary>
                            <form method="POST" action="/identity/permissions" class="mt-4">
                                <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                                <input type="hidden" name="user_id" value="<?= (int) $user['id_usuario'] ?>">
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
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
                </article>
            <?php endforeach; ?>
        </div>

        <nav class="flex items-center justify-center gap-3 mt-8">
            <?php if ($page > 1): ?>
                <a href="<?= $escape($pageUrl($page - 1)) ?>" class="px-4 py-2 rounded-xl bg-white/5 border border-white/10">Anterior</a>
            <?php endif; ?>
            <span class="text-xs text-slate-500">Página <?= $page ?> de <?= $totalPages ?></span>
            <?php if ($page < $totalPages): ?>
                <a href="<?= $escape($pageUrl($page + 1)) ?>" class="px-4 py-2 rounded-xl bg-white/5 border border-white/10">Próxima</a>
            <?php endif; ?>
        </nav>
    </div>
</section>
