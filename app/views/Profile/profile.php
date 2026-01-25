<?php
$this->partial('head', ['title' => $title]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userName = $user['nome'] ?? 'Visitante';
$userEmail = $user['email'] ?? 'não informado';
$userPhoto = $user['photo'] ?? null;
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

                        <label for="photo"
                               class="absolute inset-0 bg-black/60 flex flex-col items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                            <i data-lucide="camera" class="w-8 h-8 text-white mb-1"></i>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-white">Alterar</span>
                            <form action="/Profile/uploadPhoto" method="POST" enctype="multipart/form-data">
                                <input type="file" id="photo" name="photo" class="hidden" accept="image/*"
                                       onchange="this.form.submit()">
                            </form>
                        </label>
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
                </div>

                <div class="w-full grid grid-cols-2 gap-4 mt-8 pt-8 border-t border-white/5">
                    <div class="text-left">
                        <span class="block text-[10px] uppercase tracking-[0.15em] text-slate-500 font-bold">Status</span>
                        <span class="text-sm font-medium text-emerald-400 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> Ativo
                        </span>
                    </div>
                    <div class="text-left">
                        <span class="text-sm font-medium text-slate-200">Jan 2024</span>
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
        </aside>

        <main class="lg:col-span-8">
            <div class="rounded-3xl border border-white/5 bg-white/[0.03] backdrop-blur-2xl p-8 md:p-12 shadow-2xl">

                <header class="mb-12">
                    <h1 class="text-3xl font-bold text-white tracking-tight italic">Configurações</h1>
                    <p class="text-slate-400 mt-2">Gerencie suas credenciais de acesso e segurança.</p>
                </header>

                <form action="/Profile/changePassword" method="POST" class="space-y-10">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-10">

                        <div class="space-y-3">
                            <label for="current_password"
                                   class="text-sm font-semibold text-slate-300 ml-1 flex items-center gap-2">
                                <i data-lucide="lock" class="w-4 h-4 opacity-50"></i>
                                Senha Atual
                            </label>
                            <input type="password" id="current_password" name="current_password"
                                   class="w-full px-5 py-4 rounded-2xl bg-white/5 border border-white/10 text-white
                                          placeholder:text-slate-600 outline-none ring-offset-0 focus:ring-2
                                          focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300"
                                   placeholder="••••••••">
                        </div>

                        <div class="space-y-3">
                            <label for="new_password"
                                   class="text-sm font-semibold text-slate-300 ml-1 flex items-center gap-2">
                                <i data-lucide="key-round" class="w-4 h-4 opacity-50"></i>
                                Nova Senha
                            </label>
                            <input type="password" id="new_password" name="new_password"
                                   class="w-full px-5 py-4 rounded-2xl bg-white/5 border border-white/10 text-white
                                          placeholder:text-slate-600 outline-none ring-offset-0 focus:ring-2
                                          focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300"
                                   placeholder="••••••••">
                        </div>
                    </div>

                    <div class="bg-white/[0.02] p-6 rounded-2xl border border-white/5">
                        <div class="flex justify-between items-end mb-3">
                            <div>
                                <h4 class="text-sm font-bold text-slate-200">Segurança da senha</h4>
                                <p class="text-xs text-slate-500">Use pelo menos 8 caracteres.</p>
                            </div>
                            <span id="password-strength-text" class="text-xs font-bold uppercase tracking-widest transition-colors duration-300"></span>
                        </div>
                        <div class="h-1.5 w-full bg-white/5 rounded-full overflow-hidden">
                            <div id="password-strength"
                                 class="w-1/3 h-full bg-red-500 rounded-full transition-all duration-700"></div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-4 pt-6">
                        <button type="button"
                                class="px-6 py-4 rounded-2xl text-sm font-bold text-slate-400 hover:text-white transition-colors">
                            Descartar
                        </button>
                        <button type="submit"
                                class="cursor-pointer px-8 py-4 bg-blue-600 hover:bg-blue-500 text-white rounded-2xl font-bold
                                       shadow-lg shadow-blue-500/20 active:scale-95 transition-all flex items-center gap-3">
                            <i data-lucide="check-circle-2" class="w-5 h-5"></i>
                            Salvar Alterações
                        </button>
                    </div>

                </form>
            </div>
        </main>
    </div>
</section>