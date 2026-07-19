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

final class RegisterCompany
{
    public function __construct(
        private readonly PDO $connection,
        private readonly CompanyRepository $companies,
        private readonly ?RequestIdentity $actor = null,
        private readonly ?AuditService $audit = null
    ) {}

    public function execute(string $name, string $slug, ?string $logoPath = null): int
    {
        $company = Company::register($name, $slug, $logoPath);
        if ($this->companies->slugExists($company->slug())) {
            throw new DomainException('Já existe uma empresa com esse slug.');
        }

        $this->connection->beginTransaction();
        try {
            $companyId = $this->companies->add($company);
            $role = $this->actorRole();
            if ($this->actor !== null && $role !== null) {
                $this->companies->linkUser($companyId, $this->actor->userId(), $role['id']);
            }
            $this->audit?->log('CREATE', [
                'entity' => 'companies', 'entity_id' => $companyId,
                'new_values' => ['company_id' => $companyId, 'company_name' => $company->name(),
                    'slug' => $company->slug(), 'logo_path' => $company->logoPath(),
                    'actor_linked_to_company' => $role !== null],
            ]);
            $this->connection->commit();
            return $companyId;
        } catch (Throwable $throwable) {
            if ($this->connection->inTransaction()) { $this->connection->rollBack(); }
            throw $throwable;
        }
    }

    private function actorRole(): ?array
    {
        if ($this->actor === null) { return null; }
        $slugs = $this->actor->roleSlugs();
        $slug = in_array('super-admin', $slugs, true) ? 'super-admin' : null;
        if ($slug === null) { return null; }
        $statement = $this->connection->prepare('SELECT id, slug FROM roles WHERE slug = :slug AND is_active = 1 LIMIT 1');
        $statement->execute(['slug' => $slug]);
        $role = $statement->fetch(PDO::FETCH_ASSOC);
        return $role === false ? null : $role;
    }
}
