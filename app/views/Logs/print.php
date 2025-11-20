<?php $this->partial('head', ['title' => 'Relatório de Logs']); ?>

<div class="container mx-auto p-6 font-sans text-gray-900">
    <h1 class="text-3xl md:text-4xl font-extrabold text-center text-gradient-to-r from-blue-500 to-indigo-500 mb-10">
        Relatório de Estacionamento
    </h1>

    <div class="overflow-x-auto rounded-2xl shadow-xl border border-gray-200 bg-white">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white">
            <tr>
                <th class="px-6 py-3 text-left text-sm font-semibold uppercase tracking-wider">Data</th>
                <th class="px-6 py-3 text-left text-sm font-semibold uppercase tracking-wider">Entrada</th>
                <th class="px-6 py-3 text-left text-sm font-semibold uppercase tracking-wider">Saída</th>
                <th class="px-6 py-3 text-left text-sm font-semibold uppercase tracking-wider">Placa</th>
                <th class="px-6 py-3 text-left text-sm font-semibold uppercase tracking-wider">Valor Pago</th>
                <th class="px-6 py-3 text-left text-sm font-semibold uppercase tracking-wider">Cliente</th>
            </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200 text-sm">
            <?php if (!empty($logs)): ?>
                <?php foreach ($logs as $log): ?>
                    <tr class="hover:bg-blue-50 transition duration-200">
                        <td class="px-6 py-3"><?= htmlspecialchars($log['data']) ?></td>
                        <td class="px-6 py-3"><?= htmlspecialchars($log['hora_entrada']) ?></td>
                        <td class="px-6 py-3"><?= htmlspecialchars($log['hora_saida'] ?? '-') ?></td>
                        <td class="px-6 py-3 font-medium text-blue-700"><?= htmlspecialchars($log['placa']) ?></td>
                        <td class="px-6 py-3">
                            <span class="inline-block px-2 py-1 bg-green-100 text-green-800 font-semibold rounded-full">
                                R$<?= number_format(floatval($log['valor_pago'] ?? 0), 2, ',', '.') ?>
                            </span>
                        </td>
                        <td class="px-6 py-3"><?= htmlspecialchars($log['nome_cliente']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="px-6 py-10 text-center text-gray-400 font-medium">
                        Nenhum registro encontrado.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="flex flex-col md:flex-row justify-between mt-8 gap-4 print:hidden">
        <button onclick="window.print()"
                class="flex items-center justify-center gap-2 bg-gradient-to-r from-blue-500 to-indigo-500 text-white px-6 py-3 rounded-xl shadow-lg hover:from-indigo-500 hover:to-blue-500 transition-colors duration-300 font-semibold">
            <i data-lucide="printer" class="w-5 h-5"></i> Imprimir
        </button>

        <a href="/"
           class="flex items-center justify-center gap-2 bg-gray-200 text-gray-800 px-6 py-3 rounded-xl shadow-md hover:bg-gray-300 transition-colors duration-300 font-semibold">
            <i data-lucide="arrow-left" class="w-5 h-5"></i> Voltar para Início
        </a>
    </div>
</div>

<style>
    @media print {
        body {
            background-color: white !important;
            color: black !important;
            margin: 0;
            padding: 20px;
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
        }
        .print\:hidden { display: none !important; }
        table { width: 100%; border-collapse: collapse; }
        thead { display: table-header-group; }
        th, td { border: 1px solid #ddd; padding: 8px; font-size: 13px; text-align: left; }
        thead th { background-color: #f3f4f6 !important; color: #111; }
        tbody tr:nth-child(even) { background-color: #f9f9f9 !important; }
    }
</style>
