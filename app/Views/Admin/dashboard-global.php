<?php
$escape = fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$formatNumber = fn ($value): string => number_format((float) $value, 0, ',', '.');
$formatCurrency = fn ($value): string => 'R$ ' . number_format((float) $value, 2, ',', '.');

$cards = [
    ['label' => 'Empresas ativas', 'value' => $formatNumber($kpis['active_companies'] ?? 0), 'icon' => 'building-2', 'tone' => 'text-sky-300 bg-sky-500/10 border-sky-500/20'],
    ['label' => 'Usuários cadastrados', 'value' => $formatNumber($kpis['registered_users'] ?? 0), 'icon' => 'users', 'tone' => 'text-violet-300 bg-violet-500/10 border-violet-500/20'],
    ['label' => 'Sessões ativas', 'value' => $formatNumber($kpis['active_sessions'] ?? 0), 'icon' => 'activity', 'tone' => 'text-emerald-300 bg-emerald-500/10 border-emerald-500/20'],
    ['label' => 'Check-ins totais', 'value' => $formatNumber($kpis['completed_checkins'] ?? 0), 'icon' => 'badge-check', 'tone' => 'text-amber-300 bg-amber-500/10 border-amber-500/20'],
    ['label' => 'Faturamento total', 'value' => $formatCurrency($kpis['total_revenue'] ?? 0), 'icon' => 'banknote', 'tone' => 'text-lime-300 bg-lime-500/10 border-lime-500/20'],
    ['label' => 'Mensalistas ativos', 'value' => $formatNumber($kpis['active_monthly_contracts'] ?? 0), 'icon' => 'calendar-check-2', 'tone' => 'text-amber-300 bg-amber-500/10 border-amber-500/20'],
    ['label' => 'Receita recorrente mensal', 'value' => $formatCurrency($kpis['monthly_recurring_revenue'] ?? 0), 'icon' => 'repeat-2', 'tone' => 'text-orange-300 bg-orange-500/10 border-orange-500/20'],
    ['label' => 'Mensalidades recebidas no mês', 'value' => $formatCurrency($kpis['monthly_revenue_received'] ?? 0), 'icon' => 'wallet-cards', 'tone' => 'text-emerald-300 bg-emerald-500/10 border-emerald-500/20'],
    ['label' => 'Investigações ativas', 'value' => $formatNumber($kpis['active_investigations'] ?? 0), 'icon' => 'shield-alert', 'tone' => 'text-rose-300 bg-rose-500/10 border-rose-500/20'],
    ['label' => 'Crescimento mensal', 'value' => '+' . $formatNumber($kpis['monthly_growth'] ?? 0), 'icon' => 'trending-up', 'tone' => 'text-cyan-300 bg-cyan-500/10 border-cyan-500/20'],
];
?>

<section class="min-h-screen w-full bg-[#0b0e14] text-slate-300 p-4 sm:p-6 lg:p-8 overflow-x-hidden">
    <div class="mx-auto flex w-full max-w-7xl flex-col gap-6">
        <header class="flex flex-col gap-4 border-b border-white/10 pb-6 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-500">Governança SaaS</p>
                <h1 class="mt-2 text-3xl sm:text-4xl font-black text-white tracking-tight">Dashboard Global</h1>
                <p class="mt-2 max-w-2xl text-sm font-semibold text-slate-400">
                    Visão consolidada da plataforma, sem atalhos operacionais de check-in.
                </p>
            </div>
            <div class="flex items-center gap-3 rounded-2xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-emerald-200">
                <i data-lucide="server" class="h-5 w-5"></i>
                <span class="text-xs font-black uppercase tracking-[0.18em]">Sistema online</span>
            </div>
        </header>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            <?php foreach ($cards as $card): ?>
                <article class="min-h-36 rounded-2xl border border-white/10 bg-white/[0.03] p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-500"><?= $escape($card['label']) ?></p>
                            <p class="mt-4 text-2xl sm:text-3xl font-black text-white tracking-tight break-words"><?= $escape($card['value']) ?></p>
                        </div>
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border <?= $escape($card['tone']) ?>">
                            <i data-lucide="<?= $escape($card['icon']) ?>" class="h-5 w-5"></i>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
                <div class="flex items-center justify-between border-b border-white/10 pb-4">
                    <h2 class="text-xl font-black text-white">Top empresas por uso</h2>
                    <i data-lucide="bar-chart-3" class="h-5 w-5 text-sky-300"></i>
                </div>
                <div class="mt-4 space-y-3">
                    <?php foreach ($topCompanies as $company): ?>
                        <div class="flex items-center justify-between gap-4 rounded-xl bg-[#111827] border border-white/10 px-4 py-3">
                            <span class="min-w-0 truncate text-sm font-black text-white"><?= $escape($company['name']) ?></span>
                            <span class="shrink-0 text-xs font-bold text-slate-400"><?= $formatNumber($company['checkins'] ?? 0) ?> check-ins</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
                <div class="flex items-center justify-between border-b border-white/10 pb-4">
                    <h2 class="text-xl font-black text-white">Faturamento por empresa</h2>
                    <i data-lucide="circle-dollar-sign" class="h-5 w-5 text-lime-300"></i>
                </div>
                <div class="mt-4 space-y-3">
                    <?php foreach ($revenueByCompany as $company): ?>
                        <div class="flex items-center justify-between gap-4 rounded-xl bg-[#111827] border border-white/10 px-4 py-3">
                            <span class="min-w-0 truncate text-sm font-black text-white"><?= $escape($company['name']) ?></span>
                            <span class="shrink-0 text-xs font-bold text-lime-200"><?= $formatCurrency($company['revenue'] ?? 0) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>

        <?php $this->partial('security-alerts-widget', ['alerts' => $securityAlerts]); ?>
    </div>
</section>

<script>
    window.SwiftlyParkTenant = Object.assign(window.SwiftlyParkTenant || {}, { currentCompanyId: null });
    localStorage.removeItem('swiftlypark.current_company_id');
</script>
