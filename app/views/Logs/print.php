<?php $this->partial('head', ['title' => 'Relatório de Logs']); ?>

<div class="container mx-auto p-6 font-sans text-gray-800">
    <h1 class="text-2xl font-bold text-center text-[#2B4570] mb-6">
        Relatório de Logs de Estacionamento
    </h1>

    <div class="overflow-x-auto shadow-lg rounded-lg border border-gray-200">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-[#2B4570] text-white">
            <tr>
                <th class="px-4 py-2 text-left text-sm font-semibold uppercase">Data</th>
                <th class="px-4 py-2 text-left text-sm font-semibold uppercase">Hora Entrada</th>
                <th class="px-4 py-2 text-left text-sm font-semibold uppercase">Placa</th>
                <th class="px-4 py-2 text-left text-sm font-semibold uppercase">Valor Pago</th>
                <th class="px-4 py-2 text-left text-sm font-semibold uppercase">Cliente</th>
            </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200 text-sm">
            <?php if (!empty($logs)): ?>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td class="px-4 py-2"><?= htmlspecialchars(date('d/m/Y', strtotime($log['hora_entrada']))) ?></td>
                        <td class="px-4 py-2"><?= htmlspecialchars($log['hora_entrada']) ?></td>
                        <td class="px-4 py-2 font-semibold"><?= htmlspecialchars($log['placa']) ?></td>
                        <td class="px-4 py-2 font-semibold">R$<?= number_format(floatval($log['valor_pago'] ?? 0), 2, ',', '.') ?></td>
                        <td class="px-4 py-2"><?= htmlspecialchars($log['nome_cliente']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" class="px-4 py-4 text-center text-gray-500">
                        Nenhum registro encontrado.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="flex justify-center mt-6 gap-8">
        <button onclick="window.print()"
                class="cursor-pointer bg-[#2B4570] text-white px-5 py-2 rounded-lg hover:bg-[#1f314e] transition-colors duration-300 shadow-md print:hidden flex items-center gap-2">
            <i data-lucide="printer" class="w-5 h-5"></i> Imprimir
        </button>

        <a href="/" class="inline-flex items-center px-5 py-2 rounded-lg bg-blue-600 text-white font-semibold hover:bg-blue-700 transition-colors duration-300 shadow-md gap-2">
            <i data-lucide="arrow-left" class="w-5 h-5"></i>
            Voltar para a página inicial
        </a>
    </div>

<style>

        @media print {
            body {
                margin: 0;
                padding: 0;
                font-family: 'Courier New', Courier, monospace;
                background: white;
                color: black;
                text-transform: uppercase;
            }

            table {
                border-collapse: collapse !important;
                width: 150px;
                height: 150px;
                margin: 0 auto;
            }

            thead {
                display: none !important; /* esconde cabeçalho */
            }

            tbody tr {
                display: block;
                margin-bottom: 1rem;
                padding: 0.5rem 0;
                border: 1px solid #333;
                box-sizing: border-box;
                page-break-inside: avoid;
                height: auto; /* ajuste automático */
                width: 100%;
                border-radius: 4px;
                box-shadow: 0 0 4px #ccc;
                padding-left: 10px;
                padding-right: 10px;
                text-transform: uppercase;
            }

            tbody tr:last-child {
                margin-bottom: 0;
            }

            tbody tr td {
                display: flex;
                justify-content: space-between;
                padding: 0.1rem 0;
                font-size: 13px;
                border: none !important;
                text-align: left;
                line-height: 1.4;
                border-bottom: 1px dashed #999;
                text-transform: uppercase;
            }

            tbody tr td:last-child {
                border-bottom: none;
            }

            /* Define os labels antes do valor usando ::before, para cada td com base na posição */
            tbody tr td:first-child::before {
                content: "Data:";
                font-weight: bold;
                width: 90px;
                flex-shrink: 0;
                text-transform: uppercase;
            }

            tbody tr td:nth-child(2)::before {
                content: "Entrada:";
                font-weight: bold;
                width: 90px;
                flex-shrink: 0;
            }

            tbody tr td:nth-child(3)::before {
                content: "Placa:";
                font-weight: bold;
                width: 90px;
                flex-shrink: 0;
                text-transform: uppercase;
            }

            tbody tr td:nth-child(4)::before {
                content: "Valor Pago:";
                font-weight: bold;
                width: 90px;
                flex-shrink: 0;
                text-transform: uppercase;
            }

            tbody tr td:nth-child(5)::before {
                content: "Cliente:";
                font-weight: bold;
                width: 90px;
                flex-shrink: 0;
                text-transform: uppercase;
            }
        }

    </style>