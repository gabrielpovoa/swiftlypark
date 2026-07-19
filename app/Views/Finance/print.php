<?php
$metricLabels = [
    'gross_revenue' => 'Faturamento bruto',
    'net_revenue' => 'Faturamento líquido',
    'rotating_revenue' => 'Receita rotativa',
    'monthly_revenue' => 'Receita mensalista',
    'rotating_transactions' => 'Checkouts rotativos pagos',
    'monthly_payments' => 'Mensalidades pagas',
    'average_ticket' => 'Ticket médio',
    'occupancy_rate' => 'Taxa de ocupação (%)',
    'adjustments_total' => 'Ajustes financeiros',
    'yoy_percentage' => 'Comparativo anual (%)',
];
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 text-slate-900">
    <main class="max-w-5xl mx-auto bg-white min-h-screen p-10">
        <header class="flex justify-between items-start border-b pb-6 mb-8">
            <div class="flex items-start gap-4">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-900 text-white font-black italic">P</div>
                <?php if (!empty($companyBrand['logo_path'])): ?>
                    <img src="/uploads/<?= htmlspecialchars($companyBrand['logo_path']) ?>"
                         alt="<?= htmlspecialchars($companyBrand['name'] ?? 'Empresa') ?>"
                         class="h-20 w-20 object-contain">
                <?php endif; ?>
                <div>
                    <p class="text-xs uppercase tracking-[0.25em] text-emerald-700 font-black">SwiftlyPark</p>
                    <h1 class="text-3xl font-black mt-2">Relatório Financeiro</h1>
                    <p class="text-slate-500 mt-1">
                        <?= htmlspecialchars($companyBrand['name'] ?? 'Empresa') ?> · Período: <?= htmlspecialchars($month) ?>
                    </p>
                </div>
            </div>
            <button onclick="window.print()"
                    class="print:hidden rounded-xl bg-emerald-600 text-white font-bold px-5 py-3">
                Salvar como PDF
            </button>
        </header>

        <section class="mb-10">
            <h2 class="text-xl font-black mb-4">Indicadores</h2>
            <div class="grid grid-cols-2 gap-4">
                <?php foreach ($data['metrics'] as $key => $value): ?>
                    <article class="border rounded-2xl p-5">
                        <p class="text-xs uppercase tracking-widest text-slate-500 font-bold">
                            <?= htmlspecialchars($metricLabels[$key] ?? $key) ?>
                        </p>
                        <strong class="block text-2xl mt-2">
                            <?= htmlspecialchars($value === null ? 'Sem histórico' : (string) $value) ?>
                        </strong>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="mb-10">
            <h2 class="text-xl font-black mb-4">Faturamento por dia</h2>
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-slate-100">
                        <th class="text-left border p-3">Data</th>
                        <th class="text-left border p-3">Total</th>
                        <th class="text-left border p-3">Rotativo</th>
                        <th class="text-left border p-3">Mensalista</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['charts']['daily_revenue']['labels'] as $index => $day): ?>
                        <tr>
                            <td class="border p-3"><?= htmlspecialchars($day) ?></td>
                            <td class="border p-3">
                                R$ <?= number_format((float) $data['charts']['daily_revenue']['total'][$index], 2, ',', '.') ?>
                            </td>
                            <td class="border p-3">
                                R$ <?= number_format((float) $data['charts']['daily_revenue']['rotating'][$index], 2, ',', '.') ?>
                            </td>
                            <td class="border p-3">
                                R$ <?= number_format((float) $data['charts']['daily_revenue']['monthly'][$index], 2, ',', '.') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <section>
            <h2 class="text-xl font-black mb-4">Formas de pagamento</h2>
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-slate-100">
                        <th class="text-left border p-3">Forma</th>
                        <th class="text-left border p-3">Valor</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['charts']['payment_methods']['labels'] as $index => $method): ?>
                        <tr>
                            <td class="border p-3"><?= htmlspecialchars($method) ?></td>
                            <td class="border p-3">
                                R$ <?= number_format((float) $data['charts']['payment_methods']['values'][$index], 2, ',', '.') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </main>
</body>
</html>
