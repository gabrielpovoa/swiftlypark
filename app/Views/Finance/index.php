<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<section class="min-h-screen bg-[#0b0e14] text-slate-300 p-5 md:p-10">
    <div class="max-w-7xl mx-auto">
        <header class="flex flex-col md:flex-row md:items-end justify-between gap-5 mb-8">
            <div>
                <span class="text-emerald-500 text-xs font-black uppercase tracking-[0.3em]">Business Intelligence</span>
                <h1 class="text-4xl font-black text-white mt-2">BI Financeiro</h1>
                <p class="text-slate-500 mt-2">Receita, ocupação e meios de pagamento em uma visão consolidada.</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3">
                <input id="finance-month" type="month" value="<?= date('Y-m') ?>"
                       class="rounded-xl bg-[#131720] border border-white/10 px-4 py-3 text-white">
                <a id="finance-export"
                   href="/finance/export?month=<?= date('Y-m') ?>"
                   class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-black font-black px-5 py-3">
                    <i data-lucide="download" class="w-4 h-4"></i>
                    Exportar CSV
                </a>
                <a id="finance-print"
                   href="/finance/print?month=<?= date('Y-m') ?>"
                   target="_blank"
                   class="inline-flex items-center justify-center gap-2 rounded-xl bg-white/[0.06] hover:bg-white/[0.1] text-white font-black px-5 py-3 border border-white/10">
                    <i data-lucide="file-text" class="w-4 h-4"></i>
                    PDF
                </a>
            </div>
        </header>

        <?php if ($success): ?>
            <div class="mb-5 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 p-4 text-emerald-400 font-bold"><?= htmlspecialchars($success) ?></div>
        <?php elseif ($error): ?>
            <div class="mb-5 rounded-2xl bg-rose-500/10 border border-rose-500/20 p-4 text-rose-400 font-bold"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-6 gap-4 mb-6">
            <?php foreach ([
                ['gross-revenue', 'Faturamento bruto', 'banknote'],
                ['net-revenue', 'Receita líquida', 'circle-dollar-sign'],
                ['average-ticket', 'Ticket médio', 'receipt'],
                ['occupancy-rate', 'Taxa de ocupação', 'gauge'],
                ['adjustments-total', 'Ajustes financeiros', 'rotate-ccw'],
                ['yoy-percentage', 'Comparativo anual', 'trending-up'],
            ] as [$id, $label, $icon]): ?>
                <article class="rounded-3xl bg-white/[0.03] border border-white/10 p-5">
                    <i data-lucide="<?= $icon ?>" class="w-5 h-5 text-emerald-400"></i>
                    <p class="text-[10px] uppercase tracking-widest text-slate-500 font-black mt-5"><?= $label ?></p>
                    <strong id="<?= $id ?>" class="block text-3xl text-white font-black mt-2 animate-pulse">—</strong>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-8">
            <article class="xl:col-span-2 rounded-3xl bg-white/[0.03] border border-white/10 p-6">
                <h2 class="text-white font-black mb-5">Faturamento por dia</h2>
                <div class="h-72"><canvas id="daily-chart"></canvas></div>
            </article>
            <article class="rounded-3xl bg-white/[0.03] border border-white/10 p-6">
                <h2 class="text-white font-black mb-5">Formas de pagamento</h2>
                <div class="h-72"><canvas id="method-chart"></canvas></div>
            </article>
        </div>

        <article class="rounded-3xl bg-white/[0.03] border border-white/10 p-6 mb-8">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-3 mb-6">
                <div>
                    <span class="text-[10px] text-blue-400 font-black uppercase tracking-widest">Performance operacional</span>
                    <h2 class="text-xl text-white font-black mt-1">Operadores que mais estacionaram veículos</h2>
                    <p class="text-slate-500 text-sm mt-1">Ranking calculado pelos check-ins registrados no mês filtrado.</p>
                </div>
                <i data-lucide="trophy" class="w-8 h-8 text-amber-400"></i>
            </div>

            <div id="operator-ranking" class="space-y-4">
                <div class="rounded-2xl bg-white/[0.02] border border-white/5 p-5 text-slate-500">
                    Carregando ranking operacional...
                </div>
            </div>
        </article>

        <?php if ($canAdjust): ?>
            <article class="rounded-3xl bg-white/[0.03] border border-white/10 p-6">
                <div class="mb-5">
                    <span class="text-[10px] text-amber-400 font-black uppercase tracking-widest">Operação auditada</span>
                    <h2 class="text-xl text-white font-black mt-1">Registrar estorno</h2>
                </div>
                <form action="/finance/refund" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <select name="transaction_id" required class="rounded-xl bg-[#131720] border border-white/10 px-4 py-3 text-white">
                        <option value="">Transação</option>
                        <?php foreach ($transactions as $transaction): ?>
                            <option value="<?= (int) $transaction['id_transacao'] ?>">
                                #<?= (int) $transaction['id_transacao'] ?> · <?= htmlspecialchars($transaction['placa']) ?> · R$ <?= number_format((float) $transaction['valor'], 2, ',', '.') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" name="amount" min="0.01" step="0.01" required placeholder="Valor do estorno"
                           class="rounded-xl bg-[#131720] border border-white/10 px-4 py-3 text-white">
                    <input type="text" name="reason" minlength="10" maxlength="500" required placeholder="Justificativa detalhada"
                           class="rounded-xl bg-[#131720] border border-white/10 px-4 py-3 text-white">
                    <button class="rounded-xl bg-amber-500 hover:bg-amber-400 text-black font-black px-5 py-3">Registrar ajuste</button>
                </form>
            </article>
        <?php endif; ?>
    </div>
</section>

<script src="/js/FinanceDashboard.js"></script>
