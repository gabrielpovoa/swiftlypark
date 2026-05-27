<?php $this->partial('head', ['title' => $title]); ?>

<section class="min-h-screen w-full bg-[#0b0e14] flex items-center justify-center p-6 relative overflow-hidden">

    <div class="absolute top-0 right-0 w-[400px] h-[400px] bg-blue-600/5 blur-[100px] rounded-full pointer-events-none"></div>

    <div class="w-full max-w-lg bg-white/[0.02] backdrop-blur-xl border border-white/5 shadow-2xl rounded-[2.5rem] p-8 md:p-12 relative z-10">

        <header class="text-center mb-10">
            <div class="inline-flex p-3 rounded-2xl bg-blue-500/10 text-blue-500 mb-4">
                <i data-lucide="plus-circle" class="w-8 h-8"></i>
            </div>
            <h1 class="text-3xl md:text-4xl font-black text-white tracking-tighter italic">
                <?= htmlspecialchars($title) ?>
            </h1>
            <p class="text-slate-500 text-sm font-medium mt-2">Expanda a capacidade do seu estacionamento</p>
        </header>

        <?php if (!empty($errorMessage)): ?>
            <div role="alert" aria-live="assertive" class="flex items-center gap-3 bg-rose-500/10 border border-rose-500/20 text-rose-400 px-4 py-3 rounded-2xl mb-6 text-sm font-bold">
                <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                <?= htmlspecialchars($errorMessage) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($successMessage)): ?>
            <div role="status" aria-live="polite" class="flex items-center gap-3 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 px-4 py-3 rounded-2xl mb-6 text-sm font-bold">
                <i data-lucide="check-circle" class="w-5 h-5"></i>
                <?= htmlspecialchars($successMessage) ?>
            </div>
        <?php endif; ?>

        <form action="/CreateVacancy/store" method="POST" class="space-y-6" aria-label="Formulário para criação de novas vagas">

            <div class="space-y-2">
                <label for="category" class="text-xs font-black uppercase tracking-[0.2em] text-slate-500 ml-1">Categoria do Veículo</label>
                <div class="relative group">
                    <i data-lucide="layers" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-blue-500 transition-colors w-5 h-5"></i>

                    <select id="category" name="category" required aria-required="true"
                            class="w-full pl-12 pr-10 py-4 rounded-2xl bg-white/[0.03] border border-white/5 text-white text-sm
                                   focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:bg-white/[0.05] transition-all
                                   appearance-none cursor-pointer">
                        <option value="" class="bg-[#0b0e14]">Selecione um tipo...</option>
                        <option value="carro" class="bg-[#0b0e14]">Carro</option>
                        <option value="moto" class="bg-[#0b0e14]">Moto</option>
                        <option value="caminhao" class="bg-[#0b0e14]">Caminhão</option>
                        <option value="app" class="bg-[#0b0e14]">App (Uber/99)</option>
                    </select>

                    <div class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-slate-500">
                        <i data-lucide="chevron-down" class="w-4 h-4"></i>
                    </div>
                </div>
            </div>

            <div class="space-y-2">
                <label for="amount" class="text-xs font-black uppercase tracking-[0.2em] text-slate-500 ml-1">Quantidade de Vagas</label>
                <div class="relative group">
                    <i data-lucide="hash" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-blue-500 transition-colors w-5 h-5"></i>

                    <input type="number" id="amount" name="amount" min="1" required aria-required="true"
                           class="w-full pl-12 pr-4 py-4 rounded-2xl bg-white/[0.03] border border-white/5 text-white placeholder-slate-600 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:bg-white/[0.05] transition-all"
                           placeholder="Ex: 50">
                </div>
            </div>

            <button type="submit"
                    class="w-full py-4 rounded-2xl font-black uppercase tracking-widest text-xs text-white
                           bg-blue-600 hover:bg-blue-500 transition-all shadow-lg shadow-blue-600/20
                           flex items-center justify-center gap-3 active:scale-[0.98]">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Confirmar Criação
            </button>
        </form>

        <div class="mt-10 pt-6 border-t border-white/5">
            <a href="/" class="flex items-center justify-center gap-2 text-slate-500 hover:text-blue-400 transition-colors text-sm font-bold uppercase tracking-widest">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                Painel de Controle
            </a>
        </div>

    </div>
</section>