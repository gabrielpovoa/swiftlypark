<?php
$escape = static fn (mixed $value): string => htmlspecialchars(
    (string) $value,
    ENT_QUOTES,
    'UTF-8'
);
?>

<section class="min-h-screen bg-[#0b0e14] text-slate-300 p-5 md:p-10">
    <div class="max-w-7xl mx-auto">
        <header class="mb-8">
            <span class="text-blue-500 text-xs font-black uppercase tracking-[0.3em]">Administração Master</span>
            <h1 class="text-4xl font-black text-white mt-2">Gestão de Identidade</h1>
            <p class="text-slate-500 mt-2">Revogue acessos e conceda permissões adicionais sem alterar o papel principal.</p>
        </header>

        <?php if ($success): ?>
            <div class="mb-5 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 px-5 py-4 text-emerald-400 font-bold"><?= $escape($success) ?></div>
        <?php elseif ($error): ?>
            <div class="mb-5 rounded-2xl bg-rose-500/10 border border-rose-500/20 px-5 py-4 text-rose-400 font-bold"><?= $escape($error) ?></div>
        <?php endif; ?>

        <div class="space-y-4">
            <?php foreach ($users as $user):
                $isSelf = (int) $user['id_usuario'] === $currentUserId;
                $isRevoked = $user['deleted_at'] !== null;
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
                <a href="/identity?page=<?= $page - 1 ?>" class="px-4 py-2 rounded-xl bg-white/5 border border-white/10">Anterior</a>
            <?php endif; ?>
            <span class="text-xs text-slate-500">Página <?= $page ?> de <?= $totalPages ?></span>
            <?php if ($page < $totalPages): ?>
                <a href="/identity?page=<?= $page + 1 ?>" class="px-4 py-2 rounded-xl bg-white/5 border border-white/10">Próxima</a>
            <?php endif; ?>
        </nav>
    </div>
</section>
