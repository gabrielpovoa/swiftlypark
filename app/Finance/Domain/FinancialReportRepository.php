<?php
declare(strict_types=1);
namespace App\Finance\Domain;
interface FinancialReportRepository
{
    public function summary(string $startUtc, string $endUtc): array;
    public function revenueByDay(string $startUtc, string $endUtc): array;
    public function revenueByPaymentMethod(string $startUtc, string $endUtc): array;
    public function topParkingOperators(string $startLocal, string $endLocal, int $limit = 5): array;
    public function occupancyRate(string $startLocal, string $endLocal): float;
}
