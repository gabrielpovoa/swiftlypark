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
?>

<section class="min-h-screen bg-[#0b0e14] text-slate-300 p-5 md:p-10">
    <div class="max-w-7xl mx-auto">
        <header class="flex flex-col xl:flex-row xl:items-end justify-between gap-5 mb-8">
            <div>
                <span class="text-blue-500 text-xs font-black uppercase tracking-[0.3em]">Super-master</span>
                <h1 class="text-4xl font-black text-white mt-2">Governança SaaS</h1>
                <p class="text-slate-500 mt-2">Crie empresas, provisione usuários e controle vínculos de acesso por tenant.</p>
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

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-8">
            <section class="rounded-2xl border border-white/10 bg-white/[0.025] p-6">
                <div class="flex items-center justify-between gap-4 mb-6">
                    <div>
                        <span class="text-[10px] text-emerald-400 font-black uppercase tracking-widest">Tenant</span>
                        <h2 class="text-xl text-white font-black mt-1">Criar empresa</h2>
                    </div>
                    <i data-lucide="building-2" class="w-7 h-7 text-emerald-400"></i>
                </div>

                <form method="POST" action="/admin/companies/create" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                    <div class="md:col-span-2">
                        <label class="block text-[10px] uppercase tracking-widest text-slate-500 font-black mb-2">Nome</label>
                        <input name="name" required maxlength="255" placeholder="Minha Empresa"
                               class="w-full rounded-xl bg-[#131720] border border-white/10 px-4 py-3 text-white">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-[10px] uppercase tracking-widest text-slate-500 font-black mb-2">Slug</label>
                        <input name="slug" required pattern="[a-z0-9]+(-[a-z0-9]+)*" placeholder="minha-empresa"
                               class="w-full rounded-xl bg-[#131720] border border-white/10 px-4 py-3 text-white">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-[10px] uppercase tracking-widest text-slate-500 font-black mb-2">Logo</label>
                        <input type="file" name="logo" accept="image/png,image/jpeg,image/webp"
                               class="w-full rounded-xl bg-[#131720] border border-white/10 px-4 py-3 text-white file:mr-4 file:rounded-lg file:border-0 file:bg-emerald-500 file:px-3 file:py-2 file:text-xs file:font-black file:text-black">
                    </div>
                    <button class="md:col-span-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-black font-black px-5 py-3">
                        Criar empresa
                    </button>
                    <a href="/admin/companies" class="md:col-span-2 inline-flex items-center justify-center gap-2 rounded-xl bg-white/[0.05] hover:bg-white/[0.08] text-slate-200 font-black px-5 py-3">
                        <i data-lucide="building" class="w-4 h-4 text-emerald-400"></i>
                        Ver empresas cadastradas
                    </a>
                </form>
            </section>

            <section class="rounded-2xl border border-white/10 bg-white/[0.025] p-6">
                <div class="flex items-center justify-between gap-4 mb-6">
                    <div>
                        <span class="text-[10px] text-blue-400 font-black uppercase tracking-widest">Acesso</span>
                        <h2 class="text-xl text-white font-black mt-1">Adicionar usuário</h2>
                    </div>
                    <i data-lucide="user-plus" class="w-7 h-7 text-blue-400"></i>
                </div>

                <form method="POST" action="/admin/users/create" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                    <div>
                        <label class="block text-[10px] uppercase tracking-widest text-slate-500 font-black mb-2">Nome</label>
                        <input name="name" required maxlength="255" placeholder="Ana Operadora"
                               class="w-full rounded-xl bg-[#131720] border border-white/10 px-4 py-3 text-white">
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase tracking-widest text-slate-500 font-black mb-2">E-mail</label>
                        <input type="email" name="email" required placeholder="ana@empresa.com"
                               class="w-full rounded-xl bg-[#131720] border border-white/10 px-4 py-3 text-white">
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase tracking-widest text-slate-500 font-black mb-2">Senha inicial</label>
                        <input type="password" name="password" required minlength="12" placeholder="Mínimo 12 caracteres"
                               class="w-full rounded-xl bg-[#131720] border border-white/10 px-4 py-3 text-white">
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
                    <div class="md:col-span-2">
                        <label class="block text-[10px] uppercase tracking-widest text-slate-500 font-black mb-2">Papel</label>
                        <select name="role_id" required class="w-full rounded-xl bg-[#131720] border border-white/10 px-4 py-3 text-white">
                            <option value="">Selecione</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= (int) $role['id'] ?>"><?= $escape($role['label'] ?: $role['name']) ?> · <?= $escape($role['slug']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="md:col-span-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-black px-5 py-3">
                        Provisionar usuário
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
                                <span class="rounded-lg bg-white/[0.04] text-slate-400 px-2 py-1"><?= $escape($user['company_name'] ?? 'sem empresa') ?></span>
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
