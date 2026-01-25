<?php $this->partial('head', ['title' => $title]); ?>

    <section class="min-h-screen w-full bg-[#0b0e14] flex flex-col items-center justify-center p-6 relative overflow-hidden">

        <div class="absolute top-[-10%] right-[-10%] w-[40%] h-[40%] bg-blue-600/10 blur-[120px] rounded-full pointer-events-none"></div>
        <div class="absolute bottom-[-10%] left-[-10%] w-[30%] h-[30%] bg-indigo-600/10 blur-[120px] rounded-full pointer-events-none"></div>

        <div class="w-full max-w-md relative z-10">

            <div class="text-center mb-8">
                <div class="inline-flex p-4 rounded-3xl bg-white/[0.03] border border-white/10 mb-4 shadow-2xl transition-transform hover:scale-110 duration-500">
                    <i data-lucide="key-round" class="w-10 h-10 text-blue-500"></i>
                </div>
                <h1 class="text-3xl font-black text-white tracking-tighter italic">
                    Recuperar <span class="text-blue-500">Acesso</span>
                </h1>
                <p class="text-slate-500 text-[10px] font-black uppercase tracking-[0.3em] mt-2">Segurança SwiftlyPark</p>
            </div>

            <div class="bg-white/[0.02] backdrop-blur-2xl border border-white/5 shadow-[0_20px_50px_rgba(0,0,0,0.5)] rounded-[2.5rem] p-8 md:p-10">

                <?php if (!empty($success)): ?>
                    <div role="status" aria-live="polite" class="flex items-center gap-3 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 px-4 py-3 rounded-2xl mb-8 text-sm font-bold">
                        <i data-lucide="check-circle" class="w-5 h-5"></i>
                        <?= htmlspecialchars($success) ?>
                    </div>
                <?php elseif (!empty($error)): ?>
                    <div role="alert" aria-live="assertive" class="flex items-center gap-3 bg-rose-500/10 border border-rose-500/20 text-rose-400 px-4 py-3 rounded-2xl mb-8 text-sm font-bold animate-shake">
                        <i data-lucide="alert-circle" class="w-5 h-5"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form action="/login/recovery/send" method="POST" class="space-y-6" aria-label="Formulário de recuperação de senha">

                    <div class="space-y-2">
                        <label for="email" class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">E-mail Cadastrado</label>
                        <div class="relative group">
                            <i data-lucide="mail" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-blue-500 transition-colors w-5 h-5"></i>
                            <input type="email" id="email" name="email" required aria-required="true"
                                   value="<?= isset($email) ? htmlspecialchars($email) : '' ?>"
                                   class="w-full pl-12 pr-4 py-4 rounded-2xl bg-white/[0.03] border border-white/5 text-white placeholder-slate-600 text-sm
                                   focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:bg-white/[0.05] transition-all"
                                   placeholder="seu@email.com">
                        </div>
                    </div>

                    <?php if (!empty($showNewPassword)): ?>
                        <div class="space-y-2">
                            <label for="new_password" class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">Nova Senha</label>
                            <div class="relative group">
                                <i data-lucide="lock" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-blue-500 transition-colors w-5 h-5"></i>
                                <input type="password" id="new_password" name="new_password" required aria-required="true"
                                       class="w-full pl-12 pr-4 py-4 rounded-2xl bg-white/[0.03] border border-white/5 text-white placeholder-slate-600 text-sm
                                       focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:bg-white/[0.05] transition-all"
                                       placeholder="••••••••">
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label for="confirm_password" class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">Confirmar Nova Senha</label>
                            <div class="relative group">
                                <i data-lucide="shield-check" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-blue-500 transition-colors w-5 h-5"></i>
                                <input type="password" id="confirm_password" name="confirm_password" required aria-required="true"
                                       class="w-full pl-12 pr-4 py-4 rounded-2xl bg-white/[0.03] border border-white/5 text-white placeholder-slate-600 text-sm
                                       focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:bg-white/[0.05] transition-all"
                                       placeholder="••••••••">
                            </div>
                        </div>
                    <?php endif; ?>

                    <a href="/login"
                            class="w-full py-4 rounded-2xl bg-blue-600 hover:bg-blue-500 text-white font-black uppercase tracking-[0.2em] text-xs
                           transition-all duration-300 shadow-lg shadow-blue-600/20 active:scale-[0.98] flex items-center justify-center gap-2">
                        <i data-lucide="<?= !empty($showNewPassword) ? 'save' : 'send' ?>" class="w-4 h-4"></i>
                        <?= !empty($showNewPassword) ? 'Atualizar Senha' : 'Ir para Login' ?>
                    </a>
                </form>
            </div>

            <footer class="mt-8 text-center space-y-6">
                <p class="text-slate-500 text-sm font-medium">
                    Lembrou a senha?
                    <a href="/login" class="text-white font-bold hover:text-blue-400 transition-colors ml-1 underline-offset-4 hover:underline">
                        Fazer Login
                    </a>
                </p>
                <div class="pt-4 border-t border-white/5">
                    <a href="/CreateAcc" class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-600 hover:text-blue-500 transition-colors">
                        Não tem conta? Registre-se aqui
                    </a>
                </div>
            </footer>
        </div>
    </section>

<?php $this->partial('footer'); ?>