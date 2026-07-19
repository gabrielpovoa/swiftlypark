<?php
$escape = static fn (mixed $value): string => htmlspecialchars(
    (string) $value,
    ENT_QUOTES,
    'UTF-8'
);
$companyFilters ??= [
    'query' => '',
    'status' => 'active',
    'is_filtered' => false,
];
$companiesCount ??= count($companies);
$activeUsersCount ??= 0;
$passwordResetUsersCount ??= 0;
$success ??= null;
$error ??= null;
?>

<section class="min-h-screen bg-[#0b0e14] text-slate-300 p-5 md:p-10">
    <div class="max-w-7xl mx-auto">
        <header class="flex flex-col xl:flex-row xl:items-end justify-between gap-5 mb-8">
            <div>
                <a href="/admin" class="inline-flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-blue-400 hover:text-blue-300 mb-4">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    Governança
                </a>
                <span class="block text-emerald-400 text-xs font-black uppercase tracking-[0.3em]">Diretório</span>
                <h1 class="text-4xl font-black text-white mt-2">Empresas</h1>
                <p class="text-slate-500 mt-2">Consulte tenants cadastrados, vínculos ativos e pendências de acesso.</p>
            </div>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 text-right">
                <div class="rounded-2xl bg-white/[0.03] border border-white/10 px-5 py-4">
                    <span class="block text-[10px] uppercase tracking-widest text-slate-500 font-black">Empresas</span>
                    <strong class="text-2xl text-white"><?= (int) $companiesCount ?></strong>
                </div>
                <div class="rounded-2xl bg-white/[0.03] border border-white/10 px-5 py-4">
                    <span class="block text-[10px] uppercase tracking-widest text-slate-500 font-black">Usuários ativos</span>
                    <strong class="text-2xl text-white"><?= (int) $activeUsersCount ?></strong>
                </div>
                <div class="rounded-2xl bg-white/[0.03] border border-white/10 px-5 py-4">
                    <span class="block text-[10px] uppercase tracking-widest text-slate-500 font-black">Senha pendente</span>
                    <strong class="text-2xl text-amber-300"><?= (int) $passwordResetUsersCount ?></strong>
                </div>
                <div class="rounded-2xl bg-white/[0.03] border border-white/10 px-5 py-4">
                    <span class="block text-[10px] uppercase tracking-widest text-slate-500 font-black">Na lista</span>
                    <strong class="text-2xl text-white"><?= count($companies) ?></strong>
                </div>
            </div>
        </header>

        <?php if ($success): ?>
            <div class="mb-5 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 px-5 py-4 text-emerald-400 font-bold"><?= $escape($success) ?></div>
        <?php elseif ($error): ?>
            <div class="mb-5 rounded-2xl bg-rose-500/10 border border-rose-500/20 px-5 py-4 text-rose-400 font-bold"><?= $escape($error) ?></div>
        <?php endif; ?>

        <?php if ($canCreateCompany ?? false): ?>
            <details class="group mb-6 overflow-hidden rounded-3xl border border-emerald-500/20 bg-emerald-500/[0.035]">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-6 marker:content-none">
                    <div class="flex items-center gap-4">
                        <span class="flex h-11 w-11 items-center justify-center rounded-2xl border border-emerald-500/20 bg-emerald-500/10 text-emerald-400">
                            <i data-lucide="building-2" class="h-5 w-5"></i>
                        </span>
                        <div>
                            <span class="text-[10px] font-black uppercase tracking-widest text-emerald-400">Novo tenant</span>
                            <h2 class="mt-1 text-lg font-black text-white">Criar empresa</h2>
                        </div>
                    </div>
                    <i data-lucide="chevron-down" class="h-5 w-5 text-slate-500 transition-transform group-open:rotate-180"></i>
                </summary>
                <form method="POST" action="/admin/companies/create" enctype="multipart/form-data" class="grid grid-cols-1 gap-4 border-t border-white/10 p-6 md:grid-cols-2">
                    <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-500">
                        Nome da empresa
                        <input name="name" required maxlength="255" placeholder="SafePark"
                               class="mt-2 w-full rounded-xl border border-white/10 bg-[#131720] px-4 py-3 text-base normal-case tracking-normal text-white outline-none focus:border-emerald-500">
                    </label>
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-500">
                        Slug
                        <input name="slug" required maxlength="120" pattern="[a-z0-9]+(-[a-z0-9]+)*" placeholder="safe-park"
                               class="mt-2 w-full rounded-xl border border-white/10 bg-[#131720] px-4 py-3 text-base normal-case tracking-normal text-white outline-none focus:border-emerald-500">
                    </label>
                    <label class="company-logo-dropzone md:col-span-2 flex cursor-pointer flex-col items-center justify-center rounded-2xl border border-dashed border-white/15 bg-black/20 px-5 py-8 text-center transition hover:border-emerald-500/50 hover:bg-emerald-500/[0.04]">
                        <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="sr-only" data-company-logo-input>
                        <i data-lucide="image-up" class="mb-3 h-6 w-6 text-emerald-400"></i>
                        <strong class="text-sm text-white" data-company-logo-label>Arraste a logo ou clique para selecionar</strong>
                        <span class="mt-1 text-xs text-slate-500">PNG, JPG ou WEBP · máximo de 2 MB</span>
                    </label>
                    <div class="md:col-span-2 flex justify-end">
                        <button class="inline-flex items-center gap-2 rounded-xl bg-emerald-500 px-6 py-3 font-black text-black hover:bg-emerald-400">
                            <i data-lucide="plus" class="h-4 w-4"></i> Criar empresa
                        </button>
                    </div>
                </form>
            </details>
        <?php endif; ?>

        <section class="rounded-2xl border border-white/10 bg-white/[0.025] p-6 mb-6">
            <form method="GET" action="/admin/companies" class="grid grid-cols-1 md:grid-cols-[1fr_220px_auto] gap-3 items-end">
                <div>
                    <label class="block text-[10px] uppercase tracking-widest text-slate-500 font-black mb-2">Nome ou slug</label>
                    <div class="relative">
                        <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500"></i>
                        <input
                            name="q"
                            value="<?= $escape($companyFilters['query']) ?>"
                            placeholder="filtrar por empresa ou slug"
                            class="w-full rounded-xl bg-[#131720] border border-white/10 pl-10 pr-4 py-3 text-white outline-none focus:border-emerald-500">
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] uppercase tracking-widest text-slate-500 font-black mb-2">Status</label>
                    <select name="status" class="w-full rounded-xl bg-[#131720] border border-white/10 px-4 py-3 text-white outline-none focus:border-emerald-500">
                        <option value="active" <?= $companyFilters['status'] === 'active' ? 'selected' : '' ?>>Ativas</option>
                        <option value="inactive" <?= $companyFilters['status'] === 'inactive' ? 'selected' : '' ?>>Inativas</option>
                        <option value="all" <?= $companyFilters['status'] === 'all' ? 'selected' : '' ?>>Todas</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <button class="rounded-xl bg-emerald-500 hover:bg-emerald-400 text-black font-black px-5 py-3">
                        Filtrar
                    </button>
                    <a href="/admin/companies" class="rounded-xl bg-white/[0.05] hover:bg-white/[0.08] text-slate-300 text-center font-black px-5 py-3">
                        Limpar
                    </a>
                </div>
            </form>
        </section>

        <section class="grid grid-cols-1 lg:grid-cols-2 2xl:grid-cols-3 gap-4">
            <?php if ($companies === []): ?>
                <div class="lg:col-span-2 2xl:col-span-3 rounded-2xl bg-white/[0.025] border border-dashed border-white/10 p-8 text-sm text-slate-500">
                    Nenhuma empresa encontrada para os filtros selecionados.
                </div>
            <?php endif; ?>

            <?php foreach ($companies as $company): ?>
                <?php $isInactive = $company['deleted_at'] !== null; ?>
                <article class="min-w-0 overflow-hidden rounded-2xl border border-white/10 bg-white/[0.025] p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <strong class="block text-lg text-white truncate"><?= $escape($company['name']) ?></strong>
                                <span class="rounded-lg px-2 py-1 text-[9px] font-black uppercase tracking-widest <?= $isInactive ? 'bg-rose-500/10 text-rose-400' : 'bg-emerald-500/10 text-emerald-400' ?>">
                                    <?= $isInactive ? 'inativa' : 'ativa' ?>
                                </span>
                            </div>
                            <span class="mt-1 block text-xs text-slate-500">
                                #<?= (int) $company['id'] ?> · <?= $escape($company['slug']) ?>
                            </span>
                        </div>
                        <?php if (!empty($company['logo_path'])): ?>
                            <img src="/uploads/<?= $escape($company['logo_path']) ?>"
                                 alt="Logo <?= $escape($company['name']) ?>"
                                 class="h-12 w-12 shrink-0 object-contain">
                        <?php else: ?>
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                <i data-lucide="building-2" class="w-5 h-5"></i>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mt-5 grid grid-cols-3 gap-2">
                        <div
                            class="rounded-xl bg-black/20 border border-white/5 px-3 py-3"
                            title="Total de usuários vinculados à empresa, incluindo usuários inativos ou removidos.">
                            <span class="block text-[9px] text-slate-500 font-black uppercase tracking-widest">Vínculos</span>
                            <strong class="text-lg text-white"><?= (int) ($company['users_count'] ?? 0) ?></strong>
                            <span class="block text-[10px] text-slate-600 leading-tight">totais</span>
                        </div>
                        <div
                            class="rounded-xl bg-black/20 border border-white/5 px-3 py-3"
                            title="Usuários vinculados à empresa que ainda estão ativos no sistema.">
                            <span class="block text-[9px] text-slate-500 font-black uppercase tracking-widest">Ativos</span>
                            <strong class="text-lg text-white"><?= (int) ($company['active_users_count'] ?? 0) ?></strong>
                            <span class="block text-[10px] text-slate-600 leading-tight">usuários</span>
                        </div>
                        <div
                            class="rounded-xl bg-black/20 border border-white/5 px-3 py-3"
                            title="Usuários ativos vinculados à empresa que precisam redefinir a senha.">
                            <span class="block text-[9px] text-slate-500 font-black uppercase tracking-widest">Senha</span>
                            <strong class="text-lg text-amber-300"><?= (int) ($company['password_reset_users_count'] ?? 0) ?></strong>
                            <span class="block text-[10px] text-slate-600 leading-tight">pendente</span>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <?php
                        $roles = array_filter(array_map('trim', explode(',', (string) ($company['role_slugs'] ?? ''))));
                        ?>
                        <span class="text-[9px] font-black uppercase tracking-widest text-slate-600">Perfis vinculados</span>
                        <?php if ($roles === []): ?>
                            <span class="rounded-lg bg-white/[0.04] text-slate-500 px-2 py-1 text-[10px] font-black uppercase tracking-widest">sem papéis</span>
                        <?php endif; ?>
                        <?php foreach ($roles as $role): ?>
                            <span class="rounded-lg bg-blue-500/10 text-blue-400 px-2 py-1 text-[10px] font-black uppercase tracking-widest">
                                <?= $escape($role) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>

                    <div class="mt-5 border-t border-white/5 pt-4">
                        <?php if (!$isInactive): ?>
                            <a href="/admin/companies/company_id=<?= (int) $company['id'] ?>"
                               class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-white/[0.05] px-4 py-3 text-xs font-black text-white hover:bg-white/[0.09]">
                                Abrir empresa <i data-lucide="arrow-right" class="h-4 w-4 text-blue-400"></i>
                            </a>
                        <?php else: ?>
                            <span class="inline-flex w-full items-center justify-center rounded-xl bg-white/[0.02] px-4 py-3 text-xs font-black text-slate-600">Empresa inativa</span>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    </div>
</section>

<script>
document.querySelectorAll('[data-company-logo-input]').forEach((input) => {
    const dropzone = input.closest('label');
    const label = dropzone?.querySelector('[data-company-logo-label]');
    input.addEventListener('change', () => {
        if (label && input.files?.[0]) label.textContent = input.files[0].name;
    });
    ['dragenter', 'dragover'].forEach((eventName) => dropzone?.addEventListener(eventName, (event) => {
        event.preventDefault();
        dropzone.classList.add('border-emerald-500');
    }));
    dropzone?.addEventListener('dragleave', () => dropzone.classList.remove('border-emerald-500'));
    dropzone?.addEventListener('drop', (event) => {
        event.preventDefault();
        dropzone.classList.remove('border-emerald-500');
        if (event.dataTransfer?.files?.length) {
            input.files = event.dataTransfer.files;
            input.dispatchEvent(new Event('change'));
        }
    });
});
</script>
