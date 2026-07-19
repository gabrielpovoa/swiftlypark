<?php
$escape = static fn (mixed $value): string => htmlspecialchars(
    (string) $value,
    ENT_QUOTES,
    'UTF-8'
);
$userFilters ??= [
    'query' => '',
    'company_id' => null,
    'password_reset_required' => false,
    'is_filtered' => false,
];
$activeUsersCount ??= count($users);
$passwordResetUsersCount ??= 0;
$activeUsers ??= [];
$permissions ??= [];
?>

<section class="min-h-screen bg-[#0b0e14] text-slate-300 p-5 md:p-10">
    <div class="max-w-7xl mx-auto">
        <header class="flex flex-col xl:flex-row xl:items-end justify-between gap-5 mb-8">
            <div>
                <span class="text-blue-500 text-xs font-black uppercase tracking-[0.3em]">Super-master</span>
                <h1 class="text-4xl font-black text-white mt-2">Governança SaaS</h1>
                <p class="text-slate-500 mt-2">Acompanhe a governança da plataforma e controle vínculos de acesso por tenant.</p>
            </div>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 text-right">
                <div class="rounded-2xl bg-white/[0.03] border border-white/10 px-5 py-4">
                    <span class="block text-[10px] uppercase tracking-widest text-slate-500 font-black">Empresas</span>
                    <strong class="text-2xl text-white"><?= count($companies) ?></strong>
                    <a href="/admin/companies" class="mt-2 inline-flex items-center gap-1 text-[10px] font-black uppercase tracking-widest text-blue-400 hover:text-blue-300">
                        Ver diretório
                        <i data-lucide="arrow-up-right" class="w-3 h-3"></i>
                    </a>
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
                    <strong class="text-2xl text-white"><?= count($users) ?></strong>
                </div>
            </div>
        </header>

        <?php if ($success): ?>
            <div class="mb-5 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 px-5 py-4 text-emerald-400 font-bold"><?= $escape($success) ?></div>
        <?php elseif ($error): ?>
            <div class="mb-5 rounded-2xl bg-rose-500/10 border border-rose-500/20 px-5 py-4 text-rose-400 font-bold"><?= $escape($error) ?></div>
        <?php endif; ?>

        <div class="hidden" aria-hidden="true">
            <section class="rounded-2xl border border-white/10 bg-white/[0.025] p-6">
                <div class="flex items-center justify-between gap-4 mb-6">
                    <div>
                        <span class="text-[10px] text-cyan-400 font-black uppercase tracking-widest">Vínculo</span>
                        <h2 class="text-xl text-white font-black mt-1">Vincular usuário existente</h2>
                    </div>
                    <i data-lucide="user-round-plus" class="w-7 h-7 text-cyan-400"></i>
                </div>

                <form method="POST" action="/admin/users/link-company" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                    <div class="md:col-span-2">
                        <label class="block text-[10px] uppercase tracking-widest text-slate-500 font-black mb-2">Usuário</label>
                        <select name="user_id" required class="w-full rounded-xl bg-[#131720] border border-white/10 px-4 py-3 text-white">
                            <option value="">Selecione</option>
                            <?php foreach ($activeUsers as $activeUser): ?>
                                <option value="<?= (int) $activeUser['id_usuario'] ?>"><?= $escape($activeUser['nome']) ?> · <?= $escape($activeUser['email']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase tracking-widest text-slate-500 font-black mb-2">Empresa</label>
                        <select name="company_id" required class="w-full rounded-xl bg-[#131720] border border-white/10 px-4 py-3 text-white">
                            <option value="">Selecione</option>
                            <?php foreach ($companies as $company): ?>
                                <option value="<?= (int) $company['id'] ?>"><?= $escape($company['name']) ?> · <?= $escape($company['slug']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase tracking-widest text-slate-500 font-black mb-2">Papel</label>
                        <select name="role_id" required class="w-full rounded-xl bg-[#131720] border border-white/10 px-4 py-3 text-white">
                            <option value="">Selecione</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= (int) $role['id'] ?>"><?= $escape($role['label'] ?: $role['name']) ?> · <?= $escape($role['slug']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="md:col-span-2 rounded-xl bg-cyan-500 hover:bg-cyan-400 text-black font-black px-5 py-3">
                        Vincular à empresa
                    </button>
                </form>
            </section>

            <section class="rounded-2xl border border-white/10 bg-white/[0.025] p-6">
                <div class="flex items-center justify-between gap-4 mb-6">
                    <div>
                        <span class="text-[10px] text-rose-400 font-black uppercase tracking-widest">Acesso</span>
                        <h2 class="text-xl text-white font-black mt-1">Remover acesso a empresa</h2>
                    </div>
                    <i data-lucide="user-round-minus" class="w-7 h-7 text-rose-400"></i>
                </div>

                <form method="POST" action="/admin/users/remove-company" class="grid grid-cols-1 md:grid-cols-2 gap-4" onsubmit="return confirm('Remover o acesso deste usuário à empresa selecionada?');">
                    <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                    <div class="md:col-span-2">
                        <label class="block text-[10px] uppercase tracking-widest text-slate-500 font-black mb-2">Usuário</label>
                        <select
                            name="user_id"
                            required
                            data-remove-access-user
                            class="w-full rounded-xl bg-[#131720] border border-white/10 px-4 py-3 text-white">
                            <option value="">Selecione</option>
                            <?php foreach ($activeUsers as $activeUser): ?>
                                <?php
                                $companyOptions = array_values(array_filter(array_map(
                                    static function (string $access): ?array {
                                        [$id, $name, $slug] = array_pad(explode('::', $access, 3), 3, '');

                                        return $id !== ''
                                            ? ['id' => (int) $id, 'name' => $name, 'slug' => $slug]
                                            : null;
                                    },
                                    array_filter(explode('||', (string) ($activeUser['company_access'] ?? '')))
                                )));
                                ?>
                                <option
                                    value="<?= (int) $activeUser['id_usuario'] ?>"
                                    data-companies="<?= $escape(json_encode($companyOptions, JSON_THROW_ON_ERROR)) ?>">
                                    <?= $escape($activeUser['nome']) ?> · <?= $escape($activeUser['email']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-[10px] uppercase tracking-widest text-slate-500 font-black mb-2">Empresa</label>
                        <select
                            name="company_id"
                            required
                            data-remove-access-company
                            class="w-full rounded-xl bg-[#131720] border border-white/10 px-4 py-3 text-white">
                            <option value="">Selecione um usuário primeiro</option>
                        </select>
                    </div>
                    <button class="md:col-span-2 rounded-xl bg-rose-500/10 hover:bg-rose-500 border border-rose-500/20 text-rose-400 hover:text-white font-black px-5 py-3">
                        Remover acesso
                    </button>
                </form>
            </section>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-8">
            <section class="rounded-2xl border border-white/10 bg-white/[0.025] p-6 xl:col-span-2">
                <div class="flex items-center justify-between gap-4 mb-6">
                    <div>
                        <span class="text-[10px] text-amber-400 font-black uppercase tracking-widest">Acesso</span>
                        <h2 class="text-xl text-white font-black mt-1">Enviar senha temporária</h2>
                    </div>
                    <i data-lucide="shield-alert" class="w-7 h-7 text-amber-400"></i>
                </div>

                <form method="POST" action="/admin/users/send-temporary-password" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                    <div class="md:col-span-2">
                        <label class="block text-[10px] uppercase tracking-widest text-slate-500 font-black mb-2">Usuário</label>
                        <select name="user_id" required class="w-full rounded-xl bg-[#131720] border border-white/10 px-4 py-3 text-white">
                            <option value="">Selecione</option>
                            <?php foreach ($activeUsers as $activeUser): ?>
                                <option value="<?= (int) $activeUser['id_usuario'] ?>"><?= $escape($activeUser['nome']) ?> · <?= $escape($activeUser['email']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="md:col-span-2 rounded-2xl border border-amber-500/20 bg-amber-500/10 p-4 text-sm text-amber-200/90">
                        A senha temporária será adicionada à fila de e-mail e o usuário será obrigado a alterá-la no próximo acesso.
                    </div>
                    <button class="md:col-span-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-black font-black px-5 py-3">
                        Enviar senha temporária
                    </button>
                </form>
            </section>

        </div>

        <div class="grid grid-cols-1 gap-6">
            <section class="rounded-2xl border border-white/10 bg-white/[0.025] p-6">
                <div class="flex flex-col gap-5 mb-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <span class="text-[10px] text-blue-400 font-black uppercase tracking-widest">Diretório</span>
                            <h2 class="text-xl text-white font-black mt-1">
                                <?= $userFilters['is_filtered'] ? 'Usuários filtrados' : 'Últimos 3 usuários ativos' ?>
                            </h2>
                        </div>
                        <i data-lucide="users-round" class="w-7 h-7 text-blue-400"></i>
                    </div>

                    <form method="GET" action="/admin" class="grid grid-cols-1 md:grid-cols-[minmax(220px,1fr)_180px_auto_auto_auto] gap-3 items-end">
                        <div>
                            <label class="block text-[10px] uppercase tracking-widest text-slate-500 font-black mb-2">Nome ou e-mail</label>
                            <div class="relative">
                                <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500"></i>
                                <input
                                    name="q"
                                    value="<?= $escape($userFilters['query']) ?>"
                                    placeholder="filtrar por nome ou e-mail"
                                    class="w-full rounded-xl bg-[#131720] border border-white/10 pl-10 pr-4 py-3 text-white outline-none focus:border-blue-500">
                            </div>
                        </div>

                        <div class="md:max-w-[180px]">
                            <label class="block text-[10px] uppercase tracking-widest text-slate-500 font-black mb-2">Empresa</label>
                            <select name="company_id" class="w-full rounded-xl bg-[#131720] border border-white/10 px-4 py-3 text-white outline-none focus:border-blue-500">
                                <option value="">Todas</option>
                                <?php foreach ($companies as $company): ?>
                                    <option
                                        value="<?= (int) $company['id'] ?>"
                                        <?= (int) $company['id'] === (int) ($userFilters['company_id'] ?? 0) ? 'selected' : '' ?>>
                                        <?= $escape($company['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <label class="inline-flex h-[46px] items-center gap-3 rounded-xl bg-[#131720] border border-white/10 px-4 py-3 text-xs font-black uppercase tracking-widest text-slate-400 whitespace-nowrap">
                            <input
                                type="checkbox"
                                name="password_reset_required"
                                value="1"
                                <?= $userFilters['password_reset_required'] ? 'checked' : '' ?>
                                class="h-4 w-4 rounded border-white/20 bg-black accent-blue-500">
                            Troca pendente
                        </label>
                        <button class="h-[46px] rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-black px-4 py-3">
                            Filtrar
                        </button>
                        <a href="/admin" class="inline-flex h-[46px] items-center justify-center rounded-xl bg-white/[0.05] hover:bg-white/[0.08] text-slate-300 text-center font-black px-4 py-3">
                            Limpar
                        </a>
                    </form>
                </div>

                <div class="space-y-3">
                    <?php if ($users === []): ?>
                        <div class="rounded-xl bg-black/20 border border-dashed border-white/10 p-6 text-sm text-slate-500">
                            Nenhum usuário ativo encontrado para os filtros selecionados.
                        </div>
                    <?php endif; ?>

                    <?php foreach ($users as $user): ?>
                        <?php
                        $companyAccess = array_filter(explode('||', (string) ($user['company_access'] ?? '')));
                        ?>
                        <article class="rounded-xl bg-black/20 border border-white/5 p-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <strong class="block text-white"><?= $escape($user['nome']) ?></strong>
                                    <span class="text-xs text-slate-500"><?= $escape($user['email']) ?></span>
                                </div>
                                <span class="rounded-lg bg-blue-500/10 text-blue-400 px-3 py-1 text-[10px] font-black uppercase tracking-widest">
                                    <?= $escape($user['role_slug'] ?? 'sem papel') ?>
                                </span>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-2 text-[10px] font-black uppercase tracking-widest">
                                <?php if ($companyAccess === []): ?>
                                    <span class="rounded-lg bg-white/[0.04] text-slate-400 px-2 py-1">sem empresa</span>
                                <?php endif; ?>
                                <?php foreach ($companyAccess as $access): ?>
                                    <?php
                                    [$companyId, $companyName, $roleSlug, $extraPermissionIds, $roleLabel] = array_pad(explode('::', $access, 5), 5, '');
                                    $extraPermissionIds = array_values(array_filter(array_map(
                                        'intval',
                                        $extraPermissionIds !== '' ? explode(',', $extraPermissionIds) : []
                                    )));
                                    ?>
                                    <div class="inline-flex items-center gap-2 rounded-lg bg-white/[0.04] text-slate-400 px-2 py-1">
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-1 text-left hover:text-white transition-colors"
                                            data-company-permissions-trigger
                                            data-user-id="<?= (int) $user['id_usuario'] ?>"
                                            data-user-name="<?= $escape($user['nome']) ?>"
                                            data-company-id="<?= (int) $companyId ?>"
                                            data-company-name="<?= $escape($companyName) ?>"
                                            data-role-label="<?= $escape($roleLabel !== '' ? $roleLabel : $roleSlug) ?>"
                                            data-permissions="<?= $escape(json_encode($extraPermissionIds, JSON_THROW_ON_ERROR)) ?>">
                                            <?= $escape($companyName) ?> · <?= $escape($roleSlug) ?>
                                            <i data-lucide="sliders-horizontal" class="h-3 w-3 text-blue-400"></i>
                                        </button>
                                        <form method="POST" action="/admin/users/remove-company" class="inline" onsubmit="return confirm('Remover este acesso à empresa?');">
                                            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                                            <input type="hidden" name="user_id" value="<?= (int) $user['id_usuario'] ?>">
                                            <input type="hidden" name="company_id" value="<?= (int) $companyId ?>">
                                            <button class="inline-flex h-5 w-5 items-center justify-center rounded-md bg-rose-500/10 text-rose-400 hover:bg-rose-500 hover:text-white" title="Remover acesso">
                                                <i data-lucide="x" class="h-3 w-3"></i>
                                            </button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                                <?php if ((int) ($user['password_reset_required'] ?? 0) === 1): ?>
                                    <span class="rounded-lg bg-amber-500/10 text-amber-400 px-2 py-1">troca de senha pendente</span>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </div>
</section>

<div
    id="company-permissions-modal"
    class="fixed inset-0 z-[200] hidden items-center justify-center bg-black/70 p-4 backdrop-blur-sm"
    aria-hidden="true">
    <div class="w-full max-w-3xl rounded-2xl border border-white/10 bg-[#0f141d] shadow-2xl">
        <div class="flex items-start justify-between gap-4 border-b border-white/10 p-5">
            <div>
                <span class="text-[10px] font-black uppercase tracking-widest text-blue-400">Permissões por empresa</span>
                <h2 id="company-permissions-title" class="mt-1 text-xl font-black text-white">Perfil tenant</h2>
                <p id="company-permissions-subtitle" class="mt-1 text-sm font-bold text-slate-500"></p>
            </div>
            <button
                type="button"
                data-company-permissions-close
                class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-white/[0.05] text-slate-300 hover:bg-white/[0.1] hover:text-white">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>

        <form method="POST" action="/admin/users/company-permissions" class="p-5">
            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
            <input type="hidden" name="user_id" data-company-permissions-user-id>
            <input type="hidden" name="company_id" data-company-permissions-company-id>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-[22rem] overflow-y-auto pr-1 custom-scrollbar">
                <?php foreach ($permissions as $permission): ?>
                    <label class="flex items-start gap-3 rounded-xl border border-white/10 bg-black/20 px-4 py-3 text-sm font-bold text-slate-300 hover:border-blue-500/30">
                        <input
                            type="checkbox"
                            name="permissions[]"
                            value="<?= (int) $permission['id'] ?>"
                            data-company-permission-checkbox
                            class="mt-1 h-4 w-4 rounded border-white/20 bg-black accent-blue-500">
                        <span>
                            <span class="block text-white"><?= $escape($permission['name']) ?></span>
                            <span class="block text-[10px] uppercase tracking-widest text-slate-500"><?= $escape($permission['slug']) ?></span>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>

            <div class="mt-5 rounded-xl border border-blue-500/20 bg-blue-500/10 p-4 text-sm font-bold text-blue-100/90">
                Estas permissões são extras e valem apenas para este usuário nesta empresa. As permissões herdadas pelo papel continuam vindo do role selecionado.
            </div>

            <div class="mt-5 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button
                    type="button"
                    data-company-permissions-close
                    class="rounded-xl bg-white/[0.05] px-5 py-3 font-black text-slate-300 hover:bg-white/[0.08]">
                    Cancelar
                </button>
                <button class="rounded-xl bg-blue-600 px-5 py-3 font-black text-white hover:bg-blue-500">
                    Salvar permissões
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const userSelect = document.querySelector('[data-remove-access-user]');
    const companySelect = document.querySelector('[data-remove-access-company]');
    const modal = document.getElementById('company-permissions-modal');
    const title = document.getElementById('company-permissions-title');
    const subtitle = document.getElementById('company-permissions-subtitle');
    const userIdInput = document.querySelector('[data-company-permissions-user-id]');
    const companyIdInput = document.querySelector('[data-company-permissions-company-id]');
    const permissionCheckboxes = Array.from(document.querySelectorAll('[data-company-permission-checkbox]'));

    const renderCompanies = () => {
        if (!userSelect || !companySelect) {
            return;
        }

        const selected = userSelect.selectedOptions[0];
        const companies = selected?.dataset.companies
            ? JSON.parse(selected.dataset.companies)
            : [];

        companySelect.innerHTML = '';
        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = companies.length > 0
            ? 'Selecione'
            : 'Nenhuma empresa vinculada';
        companySelect.appendChild(placeholder);

        companies.forEach((company) => {
            const option = document.createElement('option');
            option.value = company.id;
            option.textContent = `${company.name} · ${company.slug}`;
            companySelect.appendChild(option);
        });
    };

    if (userSelect && companySelect) {
        userSelect.addEventListener('change', renderCompanies);
        renderCompanies();
    }

    const closeModal = () => {
        if (!modal) {
            return;
        }

        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.setAttribute('aria-hidden', 'true');
    };

    document.querySelectorAll('[data-company-permissions-close]').forEach((button) => {
        button.addEventListener('click', closeModal);
    });

    document.querySelectorAll('[data-company-permissions-trigger]').forEach((button) => {
        button.addEventListener('click', () => {
            if (!modal || !userIdInput || !companyIdInput) {
                return;
            }

            const selectedPermissionIds = new Set(
                JSON.parse(button.dataset.permissions || '[]').map((value) => String(value))
            );

            userIdInput.value = button.dataset.userId || '';
            companyIdInput.value = button.dataset.companyId || '';
            title.textContent = `${button.dataset.companyName || 'Empresa'} · ${button.dataset.roleLabel || 'Perfil'}`;
            subtitle.textContent = button.dataset.userName || '';

            permissionCheckboxes.forEach((checkbox) => {
                checkbox.checked = selectedPermissionIds.has(String(checkbox.value));
            });

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            modal.setAttribute('aria-hidden', 'false');
        });
    });
});
</script>
