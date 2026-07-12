<?php
$escape = static fn (mixed $value): string => htmlspecialchars(
    (string) $value,
    ENT_QUOTES,
    'UTF-8'
);
$actionStyles = [
    'CREATE' => ['emerald', 'circle-plus'],
    'UPDATE' => ['blue', 'pencil'],
    'DELETE' => ['rose', 'trash-2'],
    'UNAUTHORIZED_ACCESS_ATTEMPT' => ['amber', 'shield-alert'],
];
$canViewGlobalAudit ??= false;
$auditScope ??= 'current';
$companies ??= [];
$selectedCompanyId ??= null;
?>

<section class="min-h-screen bg-[#0b0e14] text-slate-300 p-5 md:p-10">
    <div class="max-w-7xl mx-auto">
        <header class="flex flex-col lg:flex-row lg:items-end justify-between gap-5 mb-8">
            <div>
                <span class="text-blue-500 text-xs font-black uppercase tracking-[0.3em]">Segurança e rastreabilidade</span>
                <h1 class="text-4xl font-black text-white tracking-tight mt-2">Trilha de Auditoria</h1>
                <p class="text-sm text-slate-500 mt-2">Veja claramente quem realizou cada ação e o que foi alterado.</p>
            </div>
            <div class="px-4 py-2 rounded-xl bg-white/[0.03] border border-white/10 text-xs">
                <span class="text-slate-500">Eventos encontrados:</span>
                <strong class="text-white ml-1"><?= count($logs) ?></strong>
            </div>
        </header>

        <form method="GET" action="/audit"
              class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-<?= $canViewGlobalAudit ? '7' : '5' ?> gap-4 p-5 mb-8 rounded-3xl bg-white/[0.03] border border-white/10">
            <?php if ($canViewGlobalAudit): ?>
                <div>
                    <label for="scope" class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Escopo</label>
                    <select id="scope" name="scope"
                            class="w-full rounded-xl bg-[#131720] border border-white/10 px-4 py-3 text-sm text-white">
                        <option value="global" <?= $auditScope === 'global' ? 'selected' : '' ?>>Global</option>
                        <option value="company" <?= $auditScope === 'company' ? 'selected' : '' ?>>Empresa específica</option>
                        <option value="current" <?= $auditScope === 'current' ? 'selected' : '' ?>>Empresa atual</option>
                    </select>
                </div>
                <div>
                    <label for="company_id" class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Empresa</label>
                    <select id="company_id" name="company_id"
                            class="w-full rounded-xl bg-[#131720] border border-white/10 px-4 py-3 text-sm text-white">
                        <option value="">Selecione</option>
                        <?php foreach ($companies as $company): ?>
                            <option
                                value="<?= (int) $company['id'] ?>"
                                <?= (int) $company['id'] === (int) ($selectedCompanyId ?? 0) ? 'selected' : '' ?>>
                                <?= $escape($company['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <div>
                <label for="start_date" class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Data inicial</label>
                <input id="start_date" name="start_date" type="date"
                       value="<?= $escape($filters['start_date'] ?? '') ?>"
                       class="w-full rounded-xl bg-[#131720] border border-white/10 px-4 py-3 text-sm text-white">
            </div>
            <div>
                <label for="end_date" class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Data final</label>
                <input id="end_date" name="end_date" type="date"
                       value="<?= $escape($filters['end_date'] ?? '') ?>"
                       class="w-full rounded-xl bg-[#131720] border border-white/10 px-4 py-3 text-sm text-white">
            </div>
            <div>
                <label for="actor" class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Responsável</label>
                <select id="actor" name="actor"
                        class="w-full rounded-xl bg-[#131720] border border-white/10 px-4 py-3 text-sm text-white">
                    <option value="">Todos os usuários</option>
                    <?php foreach ($actors as $actor): ?>
                        <option value="<?= $escape($actor['actor_email']) ?>"
                            <?= ($filters['actor'] ?? '') === $actor['actor_email'] ? 'selected' : '' ?>>
                            <?= $escape($actor['actor_name'] ?: $actor['actor_email']) ?>
                            — <?= $escape($actor['actor_email']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="order" class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Ordenar por</label>
                <select id="order" name="order"
                        class="w-full rounded-xl bg-[#131720] border border-white/10 px-4 py-3 text-sm text-white">
                    <option value="recent" <?= $filters['order'] === 'recent' ? 'selected' : '' ?>>Mais recentes</option>
                    <option value="user" <?= $filters['order'] === 'user' ? 'selected' : '' ?>>Usuário</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit"
                        class="flex-1 rounded-xl bg-blue-600 hover:bg-blue-500 px-4 py-3 text-sm font-black text-white transition-colors">
                    Filtrar
                </button>
                <a href="/audit" title="Limpar filtros"
                   class="rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 p-3 text-slate-400">
                    <i data-lucide="rotate-ccw" class="w-5 h-5"></i>
                </a>
            </div>
        </form>

        <?php if ($logs === []): ?>
            <div class="rounded-3xl border border-dashed border-white/10 p-16 text-center">
                <i data-lucide="search-x" class="w-12 h-12 text-slate-700 mx-auto mb-4"></i>
                <h2 class="text-white font-bold">Nenhum evento encontrado</h2>
                <p class="text-sm text-slate-600 mt-2">
                    <?php if ($canViewGlobalAudit && $auditScope === 'company'): ?>
                        <?= $selectedCompanyId === null
                            ? 'Selecione uma empresa para consultar a auditoria daquele tenant.'
                            : 'Esta empresa ainda não possui eventos de auditoria no período selecionado.' ?>
                    <?php elseif ($canViewGlobalAudit && $auditScope === 'global'): ?>
                        Nenhum evento global foi encontrado para os filtros selecionados.
                    <?php else: ?>
                        Altere ou limpe os filtros para tentar novamente.
                    <?php endif; ?>
                </p>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($logs as $log):
                    [$color, $icon] = $actionStyles[$log['action']] ?? ['slate', 'activity'];
                    ?>
                    <article class="rounded-3xl bg-white/[0.025] border border-white/10 p-5 md:p-6 hover:border-white/20 transition-colors">
                        <div class="flex flex-col md:flex-row md:items-start justify-between gap-5">
                            <div class="flex gap-4">
                                <div class="shrink-0 w-11 h-11 rounded-2xl bg-<?= $color ?>-500/10 text-<?= $color ?>-400 flex items-center justify-center border border-<?= $color ?>-500/20">
                                    <i data-lucide="<?= $icon ?>" class="w-5 h-5"></i>
                                </div>
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-<?= $color ?>-400 text-[10px] font-black uppercase tracking-widest"><?= $escape($log['action_label']) ?></span>
                                        <span class="text-slate-700">•</span>
                                        <span class="text-[10px] text-slate-500 uppercase tracking-wider"><?= $escape($log['entity_label']) ?></span>
                                    </div>
                                    <h2 class="text-white font-bold text-base mt-1"><?= $escape($log['description']) ?></h2>
                                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-2 text-xs text-slate-500">
                                        <span class="inline-flex items-center gap-1.5">
                                            <i data-lucide="user" class="w-3.5 h-3.5"></i>
                                            <strong class="text-slate-300"><?= $escape($log['actor_display']) ?></strong>
                                            <span><?= $escape($log['actor_email']) ?></span>
                                        </span>
                                        <span class="inline-flex items-center gap-1.5">
                                            <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                                            <?= $escape($log['display_date']) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <span class="text-[10px] font-mono text-slate-600">Evento #<?= $escape($log['id']) ?></span>
                        </div>

                        <?php if ($log['changes'] !== []): ?>
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 mt-5">
                                <?php foreach ($log['changes'] as $change): ?>
                                    <div class="rounded-2xl bg-black/20 border border-white/5 p-4">
                                        <span class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2"><?= $escape($change['label']) ?></span>
                                        <div class="flex items-center gap-3 text-xs">
                                            <span class="flex-1 text-rose-300 line-through decoration-rose-500/40"><?= $escape($change['before']) ?></span>
                                            <i data-lucide="arrow-right" class="w-4 h-4 text-slate-600"></i>
                                            <strong class="flex-1 text-emerald-300"><?= $escape($change['after']) ?></strong>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <details class="mt-4 group">
                            <summary class="cursor-pointer list-none text-[10px] font-bold uppercase tracking-widest text-slate-600 hover:text-slate-400">
                                Detalhes técnicos
                            </summary>
                            <div class="mt-3 rounded-xl bg-black/30 p-4 text-[10px] text-slate-500">
                                <p>IP: <?= $escape($log['ip_address']) ?> · Requisição: <?= $escape($log['request_id']) ?></p>
                                <pre class="mt-2 whitespace-pre-wrap break-all"><?= $escape($log['technical_json']) ?></pre>
                            </div>
                        </details>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
