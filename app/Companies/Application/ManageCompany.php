<?php

declare(strict_types=1);

namespace App\Companies\Application;

use App\Companies\Domain\Company;
use App\Companies\Domain\CompanyRepository;
use App\Context\RequestIdentity;
use App\Services\AuditService;
use DomainException;
use PDO;
use Throwable;

final class ManageCompany
{
    public function __construct(
        private readonly PDO $connection,
        private readonly CompanyRepository $companies,
        private readonly RequestIdentity $actor,
        private readonly AuditService $audit
    ) {}

    public function update(int $companyId, string $name, string $slug, ?string $logoPath): void
    {
        $candidate = Company::register($name, $slug, $logoPath);
        if ($companyId <= 0) { throw new DomainException('Empresa, nome e slug válido são obrigatórios.'); }
        $this->transaction(function () use ($companyId, $candidate): void {
            $current = $this->companies->findForUpdate($companyId);
            if ($current === null || $current['deleted_at'] !== null) {
                throw new DomainException('Empresa indisponível para edição.');
            }
            if ($this->companies->slugExists($candidate->slug(), $companyId)) {
                throw new DomainException('Já existe uma empresa com esse slug.');
            }
            $this->companies->update($companyId, $candidate->name(), $candidate->slug(), $candidate->logoPath());
            $this->audit->log('UPDATE', ['entity' => 'companies', 'entity_id' => $companyId,
                'new_values' => ['old_name' => $current['name'], 'old_slug' => $current['slug'],
                    'new_name' => $candidate->name(), 'new_slug' => $candidate->slug(),
                    'new_logo_path' => $candidate->logoPath() ?? $current['logo_path']]]);
        });
    }

    public function deactivate(int $companyId): void
    {
        if ($companyId <= 0) { throw new DomainException('Empresa inválida.'); }
        $this->transaction(function () use ($companyId): void {
            $company = $this->companies->findForUpdate($companyId);
            if ($company === null || $company['deleted_at'] !== null) {
                throw new DomainException('Empresa indisponível para inativação.');
            }
            if ($this->companies->isUsersLastActiveCompany($this->actor->userId(), $companyId)) {
                throw new DomainException('Você não pode inativar sua última empresa ativa.');
            }
            $linked = $this->companies->linkedUserIds($companyId);
            $this->companies->deactivate($companyId);
            $this->companies->deleteMemberships($companyId);
            $revoked = $this->companies->revokeUsersWithoutActiveCompanies($linked, $this->actor->userId());
            $this->audit->log('DELETE', ['entity' => 'companies', 'entity_id' => $companyId,
                'new_values' => ['company_name' => $company['name'], 'company_slug' => $company['slug'],
                    'revoked_company_memberships' => count($linked), 'revoked_user_ids' => $revoked]]);
        });
    }

    private function transaction(callable $operation): void
    {
        $this->connection->beginTransaction();
        try { $operation(); $this->connection->commit(); }
        catch (Throwable $throwable) {
            if ($this->connection->inTransaction()) { $this->connection->rollBack(); }
            throw $throwable;
        }
    }
}
