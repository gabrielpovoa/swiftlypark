<?php
$alerts = $alerts ?? [];
$escape = fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$labels = [
    'CROSS_TENANT_ACCESS_ATTEMPT' => 'Acesso cruzado',
    'SYSTEMATIC_TENANT_SCAN_DETECTED' => 'Varredura de tenants',
    'UNAUTHORIZED_ACCESS_ATTEMPT' => 'Permissão negada',
];
?>

<section class="bg-[#111827] border border-white/10 rounded-2xl p-5">
    <div class="flex items-center justify-between gap-4 border-b border-white/10 pb-4">
        <div>
            <p class="text-[10px] font-black uppercase tracking-[0.24em] text-rose-300">SecurityAlertsWidget</p>
            <h2 class="mt-1 text-xl font-black text-white tracking-tight">Eventos críticos recentes</h2>
        </div>
        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-rose-500/10 text-rose-300 border border-rose-500/20">
            <i data-lucide="shield-alert" class="h-5 w-5"></i>
        </div>
    </div>

    <div class="mt-4 space-y-3">
        <?php if ($alerts === []): ?>
            <div class="rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-5 text-sm font-bold text-emerald-200">
                Nenhum alerta crítico recente.
            </div>
        <?php endif; ?>

        <?php foreach ($alerts as $alert): ?>
            <?php
            $payload = json_decode((string) ($alert['new_values'] ?? ''), true);
            $reason = is_array($payload) ? ($payload['reason'] ?? $payload['error_code'] ?? 'Evento de segurança registrado.') : 'Evento de segurança registrado.';
            ?>
            <article class="rounded-xl border border-white/10 bg-white/[0.03] p-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-lg bg-rose-500/10 px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.16em] text-rose-200 border border-rose-500/20">
                                <?= $escape($labels[$alert['action']] ?? $alert['action']) ?>
                            </span>
                            <span class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500">
                                <?= $escape($alert['company_name'] ?? 'Global') ?>
                            </span>
                        </div>
                        <p class="mt-2 text-sm font-bold text-white break-words"><?= $escape($reason) ?></p>
                        <p class="mt-1 text-xs text-slate-400">
                            <?= $escape($alert['actor_email'] ?? 'desconhecido') ?> · IP <?= $escape($alert['ip_address'] ?? '-') ?>
                        </p>
                    </div>
                    <time class="shrink-0 text-xs font-bold text-slate-500"><?= $escape($alert['created_at'] ?? '') ?></time>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
