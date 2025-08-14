<?php $this->partial('head', ['title' => 'Relatório de Logs']); ?>

<div class="container mx-auto p-6 font-sans text-gray-800">
    <h1 class="text-2xl font-bold text-center text-[#2B4570] mb-6 print:text-xl">
        Relatório de Logs de Estacionamento
    </h1>

    <div class="overflow-x-auto shadow-lg rounded-lg border border-gray-200">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-[#2B4570] text-white print:bg-gray-700">
            <tr>
                <th class="px-4 py-3 text-left text-sm font-semibold uppercase">Data</th>
                <th class="px-4 py-3 text-left text-sm font-semibold uppercase">Hora Entrada</th>
                <th class="px-4 py-3 text-left text-sm font-semibold uppercase">Placa</th>
                <th class="px-4 py-3 text-left text-sm font-semibold uppercase">Valor Pago</th>
                <th class="px-4 py-3 text-left text-sm font-semibold uppercase">Cliente</th>
            </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200 text-sm">
            <?php if (!empty($logs)): ?>
                <?php foreach ($logs as $log): ?>
                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                        <td class="px-4 py-3 text-gray-700"><?= htmlspecialchars($log['data']) ?></td>
                        <td class="px-4 py-3 text-gray-700"><?= htmlspecialchars($log['hora_entrada']) ?></td>
                        <td class="px-4 py-3 font-semibold text-[#2B4570]"><?= htmlspecialchars($log['placa']) ?></td>
                        <td class="px-4 py-3 font-semibold text-green-600">R$<?= number_format(floatval($log['valor_pago'] ?? 0), 2, ',', '.') ?></td>
                        <td class="px-4 py-3 text-gray-700"><?= htmlspecialchars($log['nome_cliente']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                        Nenhum registro encontrado.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="flex justify-center mt-6 gap-8 print:hidden">
        <button onclick="window.print()"
                class="cursor-pointer bg-[#2B4570] text-white px-5 py-2 rounded-lg hover:bg-[#1f314e] transition-colors duration-300 shadow-md flex items-center gap-2">
            <i data-lucide="printer" class="w-5 h-5"></i> Imprimir
        </button>

        <a href="/" class="inline-flex items-center px-5 py-2 rounded-lg bg-blue-600 text-white font-semibold hover:bg-blue-700 transition-colors duration-300 shadow-md gap-2">
            <i data-lucide="arrow-left" class="w-5 h-5"></i>
            Voltar para a página inicial
        </a>
    </div>
</div>

<style>
    /* Estilos para a impressão */
    @media print {
        body {
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
            background-color: white !important;
            color: black !important;
            margin: 0;
            padding: 20px;
        }

        /* Oculta os botões e links */
        .print\:hidden {
            display: none !important;
        }

        h1 {
            color: black !important;
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            display: table-header-group; /* Garante que o cabeçalho se repete em cada página */
        }

        th, td {
            border: 1px solid #e5e7eb; /* Cor de borda mais suave */
            padding: 8px 16px;
            text-align: left;
            font-size: 14px;
        }

        thead th {
            background-color: #f3f4f6;
            color: #1f2937;
        }

        tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }
    }
</style>