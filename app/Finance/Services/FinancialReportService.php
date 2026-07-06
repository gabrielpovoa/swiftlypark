<?php

declare(strict_types=1);

namespace App\Finance\Services;

use App\Finance\Repositories\FinancialReportRepository;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

final class FinancialReportService
{
    public function __construct(private FinancialReportRepository $reports)
    {
    }

    public function dashboard(string $month): array
    {
        $local = new DateTimeZone('America/Sao_Paulo');
        $utc = new DateTimeZone('UTC');
        $start = DateTimeImmutable::createFromFormat('!Y-m', $month, $local);

        if ($start === false || $start->format('Y-m') !== $month) {
            throw new InvalidArgumentException('Período financeiro inválido.');
        }

        $end = $start->modify('+1 month');
        $previousYearStart = $start->modify('-1 year');
        $previousYearEnd = $end->modify('-1 year');
        $startUtc = $start->setTimezone($utc)->format('Y-m-d H:i:s');
        $endUtc = $end->setTimezone($utc)->format('Y-m-d H:i:s');
        $current = $this->reports->summary($startUtc, $endUtc);
        $previous = $this->reports->summary(
            $previousYearStart->setTimezone($utc)->format('Y-m-d H:i:s'),
            $previousYearEnd->setTimezone($utc)->format('Y-m-d H:i:s')
        );
        $gross = (float) $current['gross_revenue'];
        $adjustments = (float) $current['adjustments'];
        $previousGross = (float) $previous['gross_revenue'];
        $operators = $this->operatorRanking(
            $this->reports->topParkingOperators(
                $start->format('Y-m-d H:i:s'),
                $end->format('Y-m-d H:i:s')
            )
        );

        return [
            'period' => $month,
            'metrics' => [
                'gross_revenue' => $gross,
                'net_revenue' => $gross - $adjustments,
                'average_ticket' => (float) $current['average_ticket'],
                'occupancy_rate' => round($this->reports->occupancyRate(
                    $start->format('Y-m-d H:i:s'),
                    $end->format('Y-m-d H:i:s')
                ), 2),
                'adjustments_total' => $adjustments,
                'yoy_percentage' => $previousGross > 0
                    ? round((($gross - $previousGross) / $previousGross) * 100, 2)
                    : null,
            ],
            'charts' => [
                'daily_revenue' => $this->chart(
                    $this->reports->revenueByDay($startUtc, $endUtc),
                    'day'
                ),
                'payment_methods' => $this->paymentMethodChart(
                    $this->reports->revenueByPaymentMethod($startUtc, $endUtc)
                ),
            ],
            'operators' => [
                'top_parking' => $operators,
            ],
        ];
    }

    private function chart(array $rows, string $labelKey): array
    {
        return [
            'labels' => array_column($rows, $labelKey),
            'values' => array_map('floatval', array_column($rows, 'total')),
        ];
    }

    private function paymentMethodChart(array $rows): array
    {
        $labels = [
            'PIX' => 'Pix',
            'CARD' => 'Cartão',
            'CASH' => 'Dinheiro',
            'UNKNOWN' => 'Não informado',
        ];

        return [
            'labels' => array_map(
                static fn (array $row): string =>
                    $labels[$row['payment_method']] ?? $row['payment_method'],
                $rows
            ),
            'values' => array_map('floatval', array_column($rows, 'total')),
        ];
    }

    private function operatorRanking(array $rows): array
    {
        $total = array_sum(array_map(
            static fn (array $row): int => (int) $row['parked_count'],
            $rows
        ));

        return array_map(
            static fn (array $row): array => [
                'operator_id' => $row['operator_id'] === null
                    ? null
                    : (int) $row['operator_id'],
                'name' => self::formatOperatorName(
                    $row['operator_name'],
                    $row['operator_email']
                ),
                'email' => $row['operator_email'],
                'parked_count' => (int) $row['parked_count'],
                'generated_revenue' => (float) $row['generated_revenue'],
                'share_percentage' => $total > 0
                    ? round(((int) $row['parked_count'] / $total) * 100, 2)
                    : 0.0,
            ],
            $rows
        );
    }

    private static function formatOperatorName(
        mixed $name,
        mixed $email
    ): string {
        $name = trim((string) $name);

        if ($name === '') {
            return $email === null || trim((string) $email) === ''
                ? 'Registro sem operador vinculado'
                : self::titleCase(strstr((string) $email, '@', true) ?: $email);
        }

        return self::titleCase($name);
    }

    private static function titleCase(string $value): string
    {
        $value = mb_strtolower(trim($value), 'UTF-8');

        return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }
}
