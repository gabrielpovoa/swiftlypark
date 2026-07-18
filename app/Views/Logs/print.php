<?php $this->partial('head', ['title' => 'Relatório de Logs']); ?>

<section class="min-h-screen w-full bg-[#0b0e14] p-4 md:p-10 relative overflow-hidden">

    <div class="absolute top-0 left-1/4 w-[500px] h-[500px] bg-blue-600/5 blur-[120px] rounded-full pointer-events-none"></div>

    <div class="max-w-7xl mx-auto relative z-10">

        <header class="flex flex-col md:flex-row justify-between items-center mb-10 gap-6">
            <div class="flex flex-col md:flex-row items-center gap-4 text-center md:text-left">
                <div class="flex items-center gap-3">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-600 text-white font-black italic">P</div>
                    <?php if (!empty($companyBrand['logo_path'])): ?>
                        <img src="/uploads/<?= htmlspecialchars($companyBrand['logo_path']) ?>"
                             alt="<?= htmlspecialchars($companyBrand['name'] ?? 'Empresa') ?>"
                             class="h-14 w-14 object-contain">
                    <?php endif; ?>
                </div>
                <div>
                    <h1 class="text-3xl md:text-5xl font-black text-white tracking-tighter italic">
                        Relatório de <span class="text-blue-500">Logs</span>
                    </h1>
                    <p class="text-slate-500 text-sm font-medium mt-2 uppercase tracking-widest">
                        <?= htmlspecialchars($companyBrand['name'] ?? 'Empresa') ?> — <?= htmlspecialchars($periodLabel ?? '') ?>
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-4 print:hidden">
                <a href="/logs/print?filter=<?= rawurlencode($filter ?? '') ?>&download=1"
                   class="relative flex items-center gap-3 px-6 py-3 rounded-2xl
                          bg-emerald-500/10 border border-emerald-500/30 backdrop-blur-md
                          text-emerald-400 font-black text-xs uppercase tracking-widest
                          hover:bg-emerald-500/20 transition-all duration-300">
                    <i data-lucide="download" class="w-4 h-4"></i>
                    <span class="relative italic">Baixar XLSX</span>
                </a>

                <a href="/"
                   class="flex items-center gap-2 bg-white/5 border border-white/10 text-slate-400 px-6 py-3 rounded-2xl hover:text-white transition-all duration-300 font-bold text-sm">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i> Voltar
                </a>
            </div>
        </header>

        <div class="table-container overflow-hidden rounded-[2rem] border border-white/5 bg-white/[0.02] backdrop-blur-md shadow-2xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                    <tr class="bg-white/[0.03] border-b border-white/5">
                        <th class="px-6 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-blue-500">Data</th>
                        <th class="px-6 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 text-center">Entrada</th>
                        <th class="px-6 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 text-center">Saída</th>
                        <th class="px-6 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Placa</th>
                        <th class="px-6 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Valor Pago</th>
                        <th class="px-6 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Cliente</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.03]">
                    <?php if (!empty($logs)): ?>
                        <?php foreach ($logs as $log): ?>
                            <tr class="group hover:bg-white/[0.02] transition-colors duration-200">
                                <td class="px-6 py-5 text-sm font-medium text-slate-300"><?= htmlspecialchars($log['data']) ?></td>
                                <td class="px-6 py-5 text-sm text-slate-400 text-center font-mono italic"><?= htmlspecialchars($log['hora_entrada']) ?></td>
                                <td class="px-6 py-5 text-sm text-slate-500 text-center font-mono italic"><?= htmlspecialchars($log['hora_saida'] ?? '--:--') ?></td>
                                <td class="px-6 py-5">
                                    <span class="px-3 py-1 bg-blue-500/10 border border-blue-500/20 text-blue-400 font-bold rounded-lg tracking-widest text-xs uppercase">
                                        <?= htmlspecialchars($log['placa']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-5">
                                    <span class="text-emerald-400 font-black text-sm">
                                        R$ <?= number_format(floatval($log['valor_pago'] ?? 0), 2, ',', '.') ?>
                                    </span>
                                </td>
                                <td class="px-6 py-5 text-sm font-bold text-slate-300"><?= htmlspecialchars($log['nome_cliente']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-6 py-20 text-center text-slate-600 font-bold uppercase tracking-widest text-xs">
                                <i data-lucide="database-zap" class="w-8 h-8 mx-auto mb-4 opacity-20"></i>
                                Nenhum registro encontrado.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<style>
    /* Estilos de Impressão (Ajustados para remover border-radius) */
    @media print {
        @page { size: A4 portrait; margin: 1cm; }
        body { background: white !important; color: black !important; font-family: "Courier New", monospace !important; }
        .print\:hidden, nav, .sidebar, button, footer, .absolute { display: none !important; }

        /* Remove o arredondamento que estava cortando a borda */
        .table-container { border-radius: 0 !important; border: none !important; box-shadow: none !important; background: white !important; }

        h1 { font-size: 18pt !important; text-align: center !important; border-bottom: 2px solid #000 !important; margin-bottom: 5px !important; }

        table { width: 100% !important; border: 1px solid #000 !important; border-collapse: collapse !important; }
        th { border: 1px solid #000 !important; background: #eee !important; padding: 5px !important; font-size: 8pt !important; }
        td { border: 1px solid #000 !important; padding: 4px 6px !important; font-size: 8pt !important; }
        tbody tr:nth-child(even) { background-color: #f2f2f2 !important; }

        span { background: transparent !important; color: black !important; border: none !important; padding: 0 !important; }
    }
</style>
