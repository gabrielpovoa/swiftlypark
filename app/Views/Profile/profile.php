<?php
$userName = $user['nome'] ?? 'Visitante';
$userEmail = $user['email'] ?? 'não informado';
$userPhoto = $user['photo'] ?? null;
$roleMetadata = $roleMetadata ?? [
    'role' => 'none',
    'label' => 'Sem papel',
    'icon' => 'user',
];
?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>body {
        font-family: 'Inter', sans-serif;
    }</style>

<section class="min-h-screen w-full bg-[#0a0c10] text-slate-200 p-6 md:p-12 selection:bg-blue-500/30">

    <div class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8">

        <aside class="lg:col-span-4 flex flex-col gap-6">
            <div class="relative overflow-hidden rounded-3xl border border-white/5 bg-white/[0.03] backdrop-blur-2xl p-8 flex flex-col items-center text-center shadow-2xl">

                <div class="relative group cursor-pointer h-40 w-40 rounded-full p-1 bg-gradient-to-tr from-blue-500 to-indigo-500 shadow-2xl shadow-blue-500/20">
                    <div class="relative h-full w-full rounded-full overflow-hidden border-4 border-[#0a0c10]">
                        <?php if (!empty($userPhoto)): ?>
                            <img src="/uploads/<?= htmlspecialchars($userPhoto) ?>" alt="Perfil"
                                 class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                        <?php else: ?>
                            <div class="w-full h-full bg-slate-800 flex items-center justify-center text-5xl font-bold text-slate-400">
                                <?= strtoupper(substr($userName, 0, 1)) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($canChangePhoto)): ?>
                        <form action="/Profile/uploadPhoto" method="POST" enctype="multipart/form-data">
                            <label for="photo"
                               class="absolute inset-0 bg-black/60 flex flex-col items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                <i data-lucide="camera" class="w-8 h-8 text-white mb-1"></i>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-white">Alterar</span>
                                <input type="file" id="photo" name="photo" class="hidden" accept="image/*"
                                       onchange="this.form.submit()">
                            </label>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mt-6">
                    <h2 class="text-2xl font-bold tracking-tight text-white leading-tight">
                        <?= htmlspecialchars($userName) ?>
                    </h2>
                    <p class="text-slate-400 text-sm mt-1 flex items-center justify-center gap-2">
                        <i data-lucide="mail" class="w-3.5 h-3.5"></i>
                        <?= htmlspecialchars($userEmail) ?>
                    </p>
                    <div class="mt-3">
                        <?php $this->partial('role-badge', ['roleMetadata' => $roleMetadata]); ?>
                    </div>
                </div>

                <div class="w-full grid grid-cols-2 gap-4 mt-8 pt-8 border-t border-white/5">
                    <div class="text-left">
                        <span class="block text-[10px] uppercase tracking-[0.15em] text-slate-500 font-bold">Status</span>
                        <span class="text-sm font-medium text-emerald-400 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> Ativo
                        </span>
                    </div>
                    <div class="text-left">
                        <span class="block text-[10px] uppercase tracking-[0.15em] text-slate-500 font-bold">Proteção</span>
                        <span class="text-sm font-medium text-blue-400 flex items-center gap-1.5">
                            <i data-lucide="shield-check" class="w-4 h-4"></i>
                            RBAC ativo
                        </span>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-blue-500/10 bg-blue-500/5 p-5">
                <div class="flex gap-3">
                    <i data-lucide="shield-check" class="w-5 h-5 text-blue-400 shrink-0"></i>
                    <p class="text-xs text-blue-200/70 leading-relaxed">
                        Mantenha sua senha atualizada para garantir a máxima segurança da sua conta.
                    </p>
                </div>
            </div>

            <div class="rounded-2xl border border-white/5 bg-white/[0.025] p-5">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <span class="block text-[9px] font-black uppercase tracking-[0.2em] text-slate-600">Proteção da conta</span>
                        <h3 class="text-sm font-bold text-white mt-1">Boas práticas</h3>
                    </div>
                    <div class="w-9 h-9 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center">
                        <i data-lucide="lock-keyhole" class="w-4 h-4"></i>
                    </div>
                </div>

                <ul class="space-y-3">
                    <li class="flex items-start gap-3 text-xs text-slate-500 leading-relaxed">
                        <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-500 shrink-0 mt-0.5"></i>
                        Encerre a sessão ao utilizar um dispositivo compartilhado.
                    </li>
                    <li class="flex items-start gap-3 text-xs text-slate-500 leading-relaxed">
                        <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-500 shrink-0 mt-0.5"></i>
                        Nunca compartilhe sua senha ou códigos de recuperação.
                    </li>
                    <li class="flex items-start gap-3 text-xs text-slate-500 leading-relaxed">
                        <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-500 shrink-0 mt-0.5"></i>
                        Solicite ao administrador somente os acessos necessários.
                    </li>
                </ul>

                <a href="/login/logout"
                   class="mt-5 w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-white/5 hover:bg-rose-500/10 border border-white/10 hover:border-rose-500/20 text-slate-400 hover:text-rose-400 text-[10px] font-black uppercase tracking-widest transition-colors">
                    <i data-lucide="log-out" class="w-4 h-4"></i>
                    Encerrar sessão
                </a>
            </div>
        </aside>

        <main class="lg:col-span-8">
            <div class="rounded-3xl border border-white/5 bg-white/[0.03] backdrop-blur-2xl p-8 md:p-12 shadow-2xl">

                <?php if (!empty($message)): ?>
                    <div class="mb-8 rounded-2xl bg-blue-500/10 border border-blue-500/20 px-5 py-4 text-blue-300 font-bold">
                        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-white tracking-tight italic">Configurações</h1>
                        <p class="text-slate-400 mt-2">Gerencie suas credenciais de acesso e segurança.</p>
                    </div>

                    <details class="relative">
                        <summary class="list-none cursor-pointer inline-flex items-center gap-2 rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm font-semibold text-slate-200 hover:bg-white/10 transition-all">
                            <i data-lucide="settings-2" class="w-4 h-4"></i>
                            Editar dados
                            <i data-lucide="chevron-down" class="w-4 h-4"></i>
                        </summary>

                        <div class="absolute right-0 mt-3 w-80 rounded-2xl border border-white/10 bg-[#11151d] p-3 shadow-2xl z-10">
                            <div class="mb-3 rounded-xl bg-blue-500/10 px-3 py-2 text-[10px] font-black uppercase tracking-[0.2em] text-blue-300">
                                Editar perfil
                            </div>

                            <details class="group rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-3 mb-3" open>
                                <summary class="flex cursor-pointer list-none items-center justify-between text-sm font-semibold text-white">
                                    <span class="flex items-center gap-2">
                                        <i data-lucide="lock-keyhole" class="w-4 h-4 text-blue-400"></i>
                                        Alterar senha
                                    </span>
                                    <i data-lucide="chevron-down" class="w-4 h-4 text-slate-500 transition-transform group-open:rotate-180"></i>
                                </summary>
                                <div class="mt-4">
                                    <?php if (!empty($canChangePassword)): ?>
                                        <form action="/Profile/changePassword" method="POST" class="space-y-5">
                                            <div class="space-y-3">
                                                <label for="current_password" class="text-sm font-semibold text-slate-300 ml-1 flex items-center gap-2">
                                                    <i data-lucide="lock" class="w-4 h-4 opacity-50"></i>
                                                    Senha Atual
                                                </label>
                                                <input type="password" id="current_password" name="current_password"
                                                       class="w-full px-4 py-3 rounded-2xl bg-white/5 border border-white/10 text-white placeholder:text-slate-600 outline-none ring-offset-0 focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300"
                                                       placeholder="••••••••">
                                            </div>

                                            <div class="space-y-3">
                                                <label for="new_password" class="text-sm font-semibold text-slate-300 ml-1 flex items-center gap-2">
                                                    <i data-lucide="key-round" class="w-4 h-4 opacity-50"></i>
                                                    Nova Senha
                                                </label>
                                                <input type="password" id="new_password" name="new_password"
                                                       class="w-full px-4 py-3 rounded-2xl bg-white/5 border border-white/10 text-white placeholder:text-slate-600 outline-none ring-offset-0 focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300"
                                                       placeholder="••••••••">
                                            </div>

                                            <div class="rounded-2xl border border-white/5 bg-white/[0.02] p-4">
                                                <div class="flex justify-between items-end mb-2">
                                                    <div>
                                                        <h4 class="text-sm font-bold text-slate-200">Segurança da senha</h4>
                                                        <p class="text-xs text-slate-500">Use pelo menos 8 caracteres.</p>
                                                    </div>
                                                    <span id="password-strength-text" class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-500 transition-colors duration-300"></span>
                                                </div>
                                                <div class="h-1.5 w-full bg-white/5 rounded-full overflow-hidden">
                                                    <div id="password-strength" class="w-1/3 h-full bg-red-500 rounded-full transition-all duration-700"></div>
                                                </div>
                                            </div>

                                            <div class="flex items-center justify-end gap-3 pt-2">
                                                <button type="button" onclick="location.reload()" class="px-4 py-2 rounded-xl text-sm font-bold text-slate-400 hover:text-white transition-colors">
                                                    Descartar
                                                </button>
                                                <button type="submit" class="cursor-pointer px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-xl font-bold shadow-lg shadow-blue-500/20 active:scale-95 transition-all flex items-center gap-2">
                                                    <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                                                    Salvar
                                                </button>
                                            </div>
                                        </form>
                                    <?php else: ?>
                                        <div class="rounded-2xl border border-white/5 bg-black/20 p-4 text-center">
                                            <i data-lucide="lock-keyhole" class="w-6 h-6 text-slate-600 mx-auto mb-2"></i>
                                            <p class="text-sm text-slate-400">A alteração de senha não está disponível para seu nível de acesso.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </details>

                            <details class="group rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-3">
                                <summary class="flex cursor-pointer list-none items-center justify-between text-sm font-semibold text-white">
                                    <span class="flex items-center gap-2">
                                        <i data-lucide="user-pen" class="w-4 h-4 text-emerald-400"></i>
                                        Nome e e-mail
                                    </span>
                                    <i data-lucide="chevron-down" class="w-4 h-4 text-slate-500 transition-transform group-open:rotate-180"></i>
                                </summary>
                                <div class="mt-4">
                                    <form action="/Profile/updateProfile" method="POST" class="space-y-4">
                                        <input type="hidden" name="target_user_id" value="<?= (int) ($targetUserId ?? 0) ?>">
                                        <div class="space-y-3">
                                            <label for="profile_name" class="text-sm font-semibold text-slate-300 ml-1 flex items-center gap-2">
                                                <i data-lucide="user" class="w-4 h-4 opacity-50"></i>
                                                Nome
                                            </label>
                                            <input type="text" id="profile_name" name="nome" value="<?= htmlspecialchars($user['nome'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required class="w-full px-4 py-3 rounded-2xl bg-white/5 border border-white/10 text-white placeholder:text-slate-600 outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300">
                                        </div>
                                        <div class="space-y-3">
                                            <label for="profile_email" class="text-sm font-semibold text-slate-300 ml-1 flex items-center gap-2">
                                                <i data-lucide="mail" class="w-4 h-4 opacity-50"></i>
                                                E-mail
                                            </label>
                                            <input type="email" id="profile_email" name="email" value="<?= htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required class="w-full px-4 py-3 rounded-2xl bg-white/5 border border-white/10 text-white placeholder:text-slate-600 outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300">
                                        </div>
                                        <div class="flex items-center justify-end pt-2">
                                            <button type="submit" class="cursor-pointer px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-xl font-bold shadow-lg shadow-blue-500/20 active:scale-95 transition-all flex items-center gap-2">
                                                <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                                                Salvar dados
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </details>
                        </div>
                    </details>
                </div>

                <section class="mb-10 pb-10 border-b border-white/5">
                    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-6">
                        <div>
                            <span class="text-[10px] font-black uppercase tracking-[0.25em] text-blue-500">Seu acesso</span>
                            <h2 class="text-2xl font-black text-white mt-1">Papéis e capacidades</h2>
                            <p class="text-sm text-slate-500 mt-1">Informações somente leitura definidas pela política de acesso.</p>
                        </div>
                        <span class="text-xs text-slate-500">
                            <?= count($permissionLabels) ?> permissões efetivas
                        </span>
                    </div>

                    <div class="flex flex-wrap gap-2 mb-6">
                        <?php foreach ($roleSlugs as $roleSlug): ?>
                            <span class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-[10px] font-black uppercase tracking-wider">
                                <i data-lucide="badge-check" class="w-3.5 h-3.5"></i>
                                <?= htmlspecialchars($roleSlug, ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        <?php endforeach; ?>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
                        <?php foreach ($permissionLabels as $permission): ?>
                            <div class="flex items-center gap-3 p-3 rounded-2xl bg-black/20 border border-white/5">
                                <div class="w-8 h-8 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center shrink-0">
                                    <i data-lucide="check" class="w-4 h-4"></i>
                                </div>
                                <div class="min-w-0">
                                    <strong class="block text-xs text-slate-200 truncate">
                                        <?= htmlspecialchars($permission['name'], ENT_QUOTES, 'UTF-8') ?>
                                    </strong>
                                    <span class="block text-[9px] text-slate-600 truncate">
                                        <?= htmlspecialchars($permission['slug'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="mt-5 flex items-start gap-3 p-4 rounded-2xl bg-amber-500/5 border border-amber-500/10">
                        <i data-lucide="info" class="w-4 h-4 text-amber-400 shrink-0 mt-0.5"></i>
                        <p class="text-xs text-amber-100/60 leading-relaxed">
                            Estas permissões são informativas e não podem ser alteradas por esta página. Mudanças de acesso exigem um usuário Master autorizado.
                        </p>
                    </div>
                </section>


            </div>
        </main>
    </div>
</section>
