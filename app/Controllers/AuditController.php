<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Context\IdentityContext;
use App\Repositories\AuditLogRepository;
use App\Services\AuthorizationService;
use App\Services\AuditLogPresenter;
use Config\Database;
use Core\Controller;
use DateTimeImmutable;
use DateTimeZone;

final class AuditController extends Controller
{
    public function index(): void
    {
        (new AuthorizationService(IdentityContext::current()))
            ->check('audit.view');

        $connection = (new Database())->connect();
        $repository = new AuditLogRepository($connection);
        $filters = $this->filters();
        $presenter = new AuditLogPresenter();
        $logs = array_map(
            [$presenter, 'present'],
            $repository->findFiltered(
                $filters['actor'],
                $filters['start_at'],
                $filters['end_at'],
                $filters['order']
            )
        );

        $this->setView('Audit/index', [
            'title' => 'Auditoria - SwiftlyPark',
            'logs' => $logs,
            'actors' => $repository->findActors(),
            'filters' => $filters,
        ]);
    }

    private function filters(): array
    {
        $timezone = new DateTimeZone('America/Sao_Paulo');
        $utc = new DateTimeZone('UTC');
        $startDate = $this->validDate((string) ($_GET['start_date'] ?? ''));
        $endDate = $this->validDate((string) ($_GET['end_date'] ?? ''));
        $actor = filter_var(
            trim((string) ($_GET['actor'] ?? '')),
            FILTER_VALIDATE_EMAIL
        );
        $order = ($_GET['order'] ?? 'recent') === 'user'
            ? 'user'
            : 'recent';

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'actor' => $actor === false ? null : strtolower($actor),
            'order' => $order,
            'start_at' => $startDate === null
                ? null
                : (new DateTimeImmutable($startDate . ' 00:00:00', $timezone))
                    ->setTimezone($utc)
                    ->format('Y-m-d H:i:s'),
            'end_at' => $endDate === null
                ? null
                : (new DateTimeImmutable($endDate . ' 00:00:00', $timezone))
                    ->modify('+1 day')
                    ->setTimezone($utc)
                    ->format('Y-m-d H:i:s'),
        ];
    }

    private function validDate(string $date): ?string
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        return $parsed !== false && $parsed->format('Y-m-d') === $date
            ? $date
            : null;
    }
}
