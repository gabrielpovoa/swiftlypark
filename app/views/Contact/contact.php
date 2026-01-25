<?php $this->partial('head', ['title' => $title]); ?>

<section class="min-h-screen w-full bg-[#0b0e14] flex items-center justify-center p-6 md:p-12 relative overflow-hidden">

    <div class="absolute top-0 right-1/4 w-[500px] h-[500px] bg-blue-600/5 blur-[120px] rounded-full pointer-events-none"></div>
    <div class="absolute bottom-0 left-1/4 w-[400px] h-[400px] bg-indigo-600/5 blur-[100px] rounded-full pointer-events-none"></div>

    <div class="max-w-6xl w-full grid grid-cols-1 lg:grid-cols-12 gap-12 items-start relative z-10">

        <div class="lg:col-span-7 bg-white/[0.02] backdrop-blur-xl border border-white/5 rounded-[2.5rem] p-8 md:p-12 shadow-2xl">

            <header class="mb-10">
                <h2 class="text-3xl md:text-4xl font-black text-white tracking-tighter mb-2">
                    <?= htmlspecialchars($formTitle ?? 'Fale com a gente') ?>
                </h2>
                <p class="text-slate-500 text-sm font-medium uppercase tracking-widest">Tempo médio de resposta: 2 horas</p>
            </header>

            <?php if (!empty($errorMessage)) : ?>
                <div class="mb-8 p-4 bg-rose-500/10 border border-rose-500/20 rounded-2xl flex items-center gap-3 text-rose-400 text-sm font-bold">
                    <i data-lucide="alert-circle" class="w-5 h-5"></i>
                    <?= htmlspecialchars($errorMessage) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($successMessage)) : ?>
                <div class="mb-8 p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl flex items-center gap-3 text-emerald-400 text-sm font-bold">
                    <i data-lucide="check-circle" class="w-5 h-5"></i>
                    <?= htmlspecialchars($successMessage) ?>
                </div>
            <?php endif; ?>

            <form action="<?= htmlspecialchars($formAction ?? '/Contact/SendSMTP') ?>" method="POST" class="space-y-5">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="relative group">
                        <i data-lucide="user" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-blue-500 transition-colors w-5 h-5"></i>
                        <input type="text" name="name" value="<?= htmlspecialchars($name ?? '') ?>" required
                               class="w-full pl-12 pr-4 py-4 rounded-2xl bg-white/[0.03] border border-white/5 text-white placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:bg-white/[0.05] transition-all"
                               placeholder="Seu nome completo">
                    </div>

                    <div class="relative group">
                        <i data-lucide="mail" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-blue-500 transition-colors w-5 h-5"></i>
                        <input type="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" required
                               class="w-full pl-12 pr-4 py-4 rounded-2xl bg-white/[0.03] border border-white/5 text-white placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:bg-white/[0.05] transition-all"
                               placeholder="seu@email.com">
                    </div>
                </div>

                <div class="relative group">
                    <i data-lucide="message-square" class="absolute left-4 top-5 text-slate-500 group-focus-within:text-blue-500 transition-colors w-5 h-5"></i>
                    <textarea name="message" rows="5" required
                              class="resize-none w-full pl-12 pr-4 py-4 rounded-2xl bg-white/[0.03] border border-white/5 text-white placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:bg-white/[0.05] transition-all"
                              placeholder="Como podemos ajudar você hoje?"><?= htmlspecialchars($message ?? '') ?></textarea>
                </div>

                <button type="submit"
                        class="cursor-pointer w-full py-4 bg-blue-600 hover:bg-blue-500 text-white font-black uppercase tracking-widest text-xs rounded-2xl flex items-center justify-center gap-3 shadow-lg shadow-blue-600/20 active:scale-[0.98] transition-all">
                    <i data-lucide="send" class="w-4 h-4"></i>
                    <?= htmlspecialchars($buttonText ?? 'Enviar Solicitação') ?>
                </button>
            </form>
        </div>

        <div class="lg:col-span-5 flex flex-col gap-6">

            <?php if (!empty($ticketNumber)) : ?>
                <div class="bg-gradient-to-br from-emerald-600 to-teal-700 rounded-[2.5rem] p-10 text-white shadow-2xl shadow-emerald-900/20 animate-fadeIn">
                    <div class="bg-white/20 w-16 h-16 rounded-2xl flex items-center justify-center mb-6">
                        <i data-lucide="ticket" class="w-8 h-8"></i>
                    </div>
                    <h3 class="text-2xl font-black tracking-tight mb-2">Ticket Gerado!</h3>
                    <p class="text-emerald-100/80 text-sm mb-6 font-medium">Anote o código do seu atendimento para consultas futuras.</p>
                    <div class="bg-black/20 rounded-2xl p-4 text-center border border-white/10">
                        <span class="text-3xl font-mono font-black tracking-[0.3em]"><?= htmlspecialchars($ticketNumber) ?></span>
                    </div>
                </div>
            <?php else: ?>
                <div class="bg-white/[0.03] border border-white/5 rounded-[2.5rem] p-8">
                    <h3 class="text-white font-bold mb-6 flex items-center gap-2">
                        <i data-lucide="help-circle" class="w-5 h-5 text-blue-500"></i>
                        Canais de Suporte
                    </h3>

                    <div class="space-y-4">
                        <div class="flex items-center gap-4 p-4 bg-white/[0.02] rounded-2xl border border-white/5">
                            <div class="p-3 bg-blue-500/10 rounded-xl text-blue-500">
                                <i data-lucide="phone" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <p class="text-[10px] uppercase font-bold text-slate-500 tracking-widest">Telefone</p>
                                <p class="text-white font-bold text-sm"><?= htmlspecialchars($contactPhone ?? '0800-123-456') ?></p>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 p-4 bg-white/[0.02] rounded-2xl border border-white/5">
                            <div class="p-3 bg-indigo-500/10 rounded-xl text-indigo-500">
                                <i data-lucide="mail" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <p class="text-[10px] uppercase font-bold text-slate-500 tracking-widest">E-mail</p>
                                <p class="text-white font-bold text-sm"><?= htmlspecialchars($contactEmail ?? 'suporte@swiftlypark.com') ?></p>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 p-4 bg-white/[0.02] rounded-2xl border border-white/5">
                            <div class="p-3 bg-emerald-500/10 rounded-xl text-emerald-500">
                                <i data-lucide="clock" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <p class="text-[10px] uppercase font-bold text-slate-500 tracking-widest">Atendimento</p>
                                <p class="text-white font-bold text-sm"><?= htmlspecialchars($contactHours ?? 'Seg a Sex, 08h - 18h') ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-blue-600 rounded-[2.5rem] p-8 text-white relative overflow-hidden group">
                    <div class="relative z-10">
                        <h4 class="font-black text-xl mb-2 italic">FAQ Swiftly</h4>
                        <p class="text-blue-100 text-sm leading-relaxed mb-4">Dúvidas comuns sobre faturamento e acesso? Confira nossa central de ajuda.</p>
                        <a href="#" class="inline-flex items-center gap-2 text-xs font-black uppercase tracking-widest bg-white text-blue-600 px-5 py-3 rounded-xl hover:bg-blue-50 transition-colors">
                            Acessar FAQ
                        </a>
                    </div>
                    <i data-lucide="message-circle" class="absolute -bottom-4 -right-4 w-32 h-32 text-white/10 -rotate-12 group-hover:scale-110 transition-transform"></i>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>