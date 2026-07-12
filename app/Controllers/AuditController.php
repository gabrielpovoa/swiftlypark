<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Authorization\Repositories\RbacRepository;
use App\Authorization\Services\RolePermissionResolver;
use App\Context\IdentityContext;
use App\Repositories\AuditLogRepository;
use App\Repositories\TenantRepository;
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
        $connection = (new Database())->connect();
        $repository = new AuditLogRepository($connection);
        $filters = $this->filters();
        $presenter = new AuditLogPresenter();
        $identity = IdentityContext::current();
        $canViewGlobalAudit = $this->hasGlobalPlatformRole($connection, $identity->userId());

        if (!$canViewGlobalAudit) {
            (new AuthorizationService($identity))->check('audit.view');
        }

        $scope = $canViewGlobalAudit
            ? (string) ($_GET['scope'] ?? 'global')
            : 'current';
        $scope = in_array($scope, ['global', 'company', 'current'], true)
            ? $scope
            : 'global';
        $companyId = null;

        if ($canViewGlobalAudit && $scope === 'company') {
            $companyId = filter_var(
                $_GET['company_id'] ?? null,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            );
        }

        $requiresCompanySelection = $canViewGlobalAudit
            && $scope === 'company'
            && ($companyId === false || $companyId === null);
        $rawLogs = match (true) {
            $requiresCompanySelection => [],
            $canViewGlobalAudit && $scope === 'global' => $repository
                ->getGlobalLogs(
                    $filters['actor'],
                    $filters['start_at'],
                    $filters['end_at'],
                    $filters['order']
                ),
            $canViewGlobalAudit && $scope === 'company' && $companyId !== false && $companyId !== null => $repository
                ->getLogsByCompany(
                    (int) $companyId,
                    $filters['actor'],
                    $filters['start_at'],
                    $filters['end_at'],
                    $filters['order']
                ),
            default => $repository->findFiltered(
                $filters['actor'],
                $filters['start_at'],
                $filters['end_at'],
                $filters['order']
            ),
        };
        $logs = array_map(
            [$presenter, 'present'],
            $rawLogs
        );
        $actors = match (true) {
            $requiresCompanySelection => [],
            $canViewGlobalAudit && $scope === 'global' => $repository->findGlobalActors(),
            $canViewGlobalAudit && $scope === 'company' && $companyId !== false && $companyId !== null => $repository->findActorsByCompany((int) $companyId),
            default => $repository->findActors(),
        };

        $this->setView('Audit/index', [
            'title' => 'Auditoria - SwiftlyPark',
            'logs' => $logs,
            'actors' => $actors,
            'filters' => $filters,
            'canViewGlobalAudit' => $canViewGlobalAudit,
            'auditScope' => $scope,
            'selectedCompanyId' => $companyId !== false && $companyId !== null ? (int) $companyId : null,
            'companies' => $canViewGlobalAudit
                ? (new TenantRepository($connection))->findActiveCompanies()
                : [],
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

    private function hasGlobalPlatformRole(\PDO $connection, int $userId): bool
    {
        $authorization = (new RolePermissionResolver(
            new RbacRepository($connection)
        ))->resolve($userId, null);
        $roles = $authorization->roleSlugs();

        return in_array('master', $roles, true)
            || in_array('super-admin', $roles, true);
    }
}
