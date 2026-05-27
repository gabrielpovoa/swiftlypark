<?php $this->partial('head', ['title' => $title]); ?>

    <section class="min-h-screen w-full bg-[#0b0e14] flex flex-col items-center justify-center p-6 relative overflow-hidden">

        <div class="absolute bottom-[-10%] left-[-10%] w-[40%] h-[40%] bg-blue-600/10 blur-[120px] rounded-full pointer-events-none"></div>
        <div class="absolute top-[-10%] right-[-10%] w-[30%] h-[30%] bg-indigo-600/10 blur-[120px] rounded-full pointer-events-none"></div>

        <div class="w-full max-w-lg relative z-10">

            <div class="text-center mb-8">
                <div class="inline-flex p-4 rounded-3xl bg-white/[0.03] border border-white/10 mb-4 shadow-2xl">
                    <i data-lucide="user-plus" class="w-10 h-10 text-blue-500"></i>
                </div>
                <h1 class="text-4xl font-black text-white tracking-tighter italic">
                    Criar <span class="text-blue-500">Conta</span>
                </h1>
                <p class="text-slate-500 text-[10px] font-black uppercase tracking-[0.3em] mt-2">Inicie sua jornada no SwiftlyPark</p>
            </div>

            <div class="bg-white/[0.02] backdrop-blur-2xl border border-white/5 shadow-[0_20px_50px_rgba(0,0,0,0.5)] rounded-[2.5rem] p-8 md:p-12">

                <?php if (!empty($error)) : ?>
                    <div role="alert" class="flex items-center gap-3 bg-rose-500/10 border border-rose-500/20 text-rose-400 px-4 py-3 rounded-2xl mb-8 text-sm font-bold animate-shake">
                        <i data-lucide="info" class="w-5 h-5"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form action="/CreateAcc/create" method="POST" class="space-y-5" aria-label="Formulário de cadastro">

                    <div class="space-y-2">
                        <label for="name" class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">Nome Completo</label>
                        <div class="relative group">
                            <i data-lucide="user" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-blue-500 transition-colors w-5 h-5"></i>
                            <input type="text" id="name" name="name" required
                                   class="w-full pl-12 pr-4 py-4 rounded-2xl bg-white/[0.03] border border-white/5 text-white placeholder-slate-600 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:bg-white/[0.05] transition-all"
                                   placeholder="Digite seu nome">
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label for="email" class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">E-mail Profissional</label>
                        <div class="relative group">
                            <i data-lucide="mail" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-blue-500 transition-colors w-5 h-5"></i>
                            <input type="email" id="email" name="email" required
                                   class="w-full pl-12 pr-4 py-4 rounded-2xl bg-white/[0.03] border border-white/5 text-white placeholder-slate-600 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:bg-white/[0.05] transition-all"
                                   placeholder="exemplo@swiftly.com">
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label for="password" class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">Senha de Acesso</label>
                        <div class="relative group">
                            <i data-lucide="lock" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-blue-500 transition-colors w-5 h-5"></i>
                            <input type="password" id="password" name="password" required
                                   class="w-full pl-12 pr-4 py-4 rounded-2xl bg-white/[0.03] border border-white/5 text-white placeholder-slate-600 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:bg-white/[0.05] transition-all"
                                   placeholder="Mínimo 8 caracteres">
                        </div>
                    </div>

                    <button type="submit"
                            class="w-full py-4 mt-4 rounded-2xl bg-blue-600 hover:bg-blue-500 text-white font-black uppercase tracking-[0.2em] text-xs
                               transition-all duration-300 shadow-lg shadow-blue-600/20 active:scale-[0.98] flex items-center justify-center gap-2">
                        Registrar Conta
                        <i data-lucide="chevron-right" class="w-4 h-4"></i>
                    </button>

                </form>
            </div>

            <div class="mt-10 text-center">
                <p class="text-slate-500 text-sm font-medium">
                    Já possui uma conta ativa?
                    <a href="/login" class="text-white font-bold hover:text-blue-400 underline-offset-4 hover:underline transition-all ml-1">
                        Acessar Painel
                    </a>
                </p>
            </div>
        </div>
    </section>

<?php $this->partial('footer'); ?>