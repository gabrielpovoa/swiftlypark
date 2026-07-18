<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Context\IdentityContext;
use App\Identity\Services\UserProvisioningService;
use App\Repositories\AuditLogRepository;
use App\Exceptions\ForbiddenException;
use App\Services\AuditService;
use App\Services\PasswordGeneratorService;
use App\Services\PasswordRecoveryMailer;
use Config\Database;
use Core\Controller;
use DateTimeImmutable;
use DateTimeZone;
use DomainException;
use Throwable;
use App\Security\InputSanitizer;
use App\Finance\Repositories\MonthlyContractRepository;

final class AdminProvisioningController extends Controller
{
    public function index(): void
    {
        $this->startSession();
        $this->assertGovernanceAdmin();

        $connection = (new Database())->connect();
        $filters = $this->userFilters();

        $this->setView('Admin/provisioning', [
            'title' => 'Governança SaaS - SwiftlyPark',
            'companies' => $this->companies($connection),
            'roles' => $this->assignableRoles($connection),
            'permissions' => $this->permissions($connection),
            'activeUsers' => $this->activeUsers($connection),
            'users' => $this->users($connection, $filters),
            'userFilters' => $filters,
            'activeUsersCount' => $this->activeUsersCount($connection),
            'passwordResetUsersCount' => $this->passwordResetUsersCount($connection),
            'csrfToken' => $this->csrfToken(),
            'success' => $_SESSION['admin_success'] ?? null,
            'error' => $_SESSION['admin_error'] ?? null,
        ]);

        unset($_SESSION['admin_success'], $_SESSION['admin_error']);
    }

    public function sendTemporaryPassword(): void
    {
        $this->startSession();

        if (!$this->hasValidAdminCsrf()) {
            $_SESSION['admin_error'] = 'A sessão expirou. Tente novamente.';
            $this->redirect();
        }

        $payload = $this->payload();
        $connection = (new Database())->connect();

        try {
            $this->assertGovernanceAdmin();

            $userId = (int) ($payload['user_id'] ?? 0);
            if ($userId <= 0) {
                throw new DomainException('Selecione um usuário válido.');
            }

            $user = $this->userForPasswordReset($connection, $userId);
            if ($user === null || $user['deleted_at'] !== null) {
                throw new DomainException('Usuário indisponível para redefinição.');
            }

            $temporaryPassword = (new PasswordGeneratorService())->temporary();
            $passwordHash = password_hash($temporaryPassword, PASSWORD_DEFAULT);

            $connection->beginTransaction();
            try {
                $updateUser = $connection->prepare(
                    'UPDATE usuario
                     SET senha_hash = :password_hash,
                         password_reset_required = 1
                     WHERE id_usuario = :user_id'
                );
                $updateUser->execute([
                    'password_hash' => $passwordHash,
                    'user_id' => $userId,
                ]);

                if ($updateUser->rowCount() !== 1) {
                    throw new DomainException('Não foi possível atualizar a senha do usuário.');
                }

                $updateLogin = $connection->prepare(
                    'UPDATE login
                     SET senha = :password_hash
                     WHERE id_login = :login_id'
                );
                $updateLogin->execute([
                    'password_hash' => $passwordHash,
                    'login_id' => $user['id_login'],
                ]);

                $mailer = new PasswordRecoveryMailer();
                $mailer->sendTemporaryPassword($user['email'], $temporaryPassword);

                $connection->commit();
                $_SESSION['admin_success'] = 'Senha temporária enviada com sucesso.';
            } catch (Throwable $throwable) {
                if ($connection->inTransaction()) {
                    $connection->rollBack();
                }

                throw $throwable;
            }
        } catch (DomainException $exception) {
            $_SESSION['admin_error'] = $exception->getMessage();
        } catch (Throwable $throwable) {
            error_log($throwable->getMessage());
            $_SESSION['admin_error'] = 'Não foi possível enviar a senha temporária.';
        }

        $this->redirect();
    }

    public function createUser(): void
    {
        $this->respond(function (): array {
            $this->assertValidFormRequest();
            $payload = $this->payload();
            $connection = (new Database())->connect();
            $identity = IdentityContext::current();
            $service = new UserProvisioningService(
                $connection,
                $identity,
                new AuditService(
                    new AuditLogRepository($connection),
                    $identity
                )
            );

            $userId = $service->create(
                (string) ($payload['name'] ?? ''),
                (string) ($payload['email'] ?? ''),
                (int) ($payload['company_id'] ?? 0),
                (int) ($payload['role_id'] ?? 0)
            );

            return ['user_id' => $userId];
        }, 'Usuário provisionado com sucesso.');
    }

    public function linkExistingUser(): void
    {
        $this->respond(function (): array {
            $this->assertValidFormRequest();
            $payload = $this->payload();
            $connection = (new Database())->connect();
            $identity = IdentityContext::current();
            $service = new UserProvisioningService(
                $connection,
                $identity,
                new AuditService(
                    new AuditLogRepository($connection),
                    $identity
                )
            );

            $service->linkExistingUserToCompany(
                (int) ($payload['user_id'] ?? 0),
                (int) ($payload['company_id'] ?? 0),
                (int) ($payload['role_id'] ?? 0)
            );

            return ['linked' => true];
        }, 'Usuário vinculado à empresa com sucesso.');
    }

    public function removeCompanyAccess(): void
    {
        $this->respond(function (): array {
            $this->assertValidFormRequest();
            $payload = $this->payload();
            $connection = (new Database())->connect();
            $identity = IdentityContext::current();
            $service = new UserProvisioningService(
                $connection,
                $identity,
                new AuditService(
                    new AuditLogRepository($connection),
                    $identity
                )
            );

            $service->removeCompanyAccess(
                (int) ($payload['user_id'] ?? 0),
                (int) ($payload['company_id'] ?? 0)
            );

            return ['removed' => true];
        }, 'Acesso à empresa removido com sucesso.');
    }

    public function syncCompanyPermissions(): void
    {
        $this->respond(function (): array {
            $this->assertValidFormRequest();
            $payload = $this->payload();
            $connection = (new Database())->connect();
            $userId = (int) ($payload['user_id'] ?? 0);
            $companyId = (int) ($payload['company_id'] ?? 0);
            $permissionIds = $payload['permissions'] ?? [];

            $this->syncTenantPermissionOverrides(
                $connection,
                $userId,
                $companyId,
                is_array($permissionIds) ? $permissionIds : []
            );

            return ['updated' => true];
        }, 'Permissões do perfil na empresa atualizadas com sucesso.');
    }

    public function createCompany(): void
    {
        $this->respond(function (): array {
            $this->assertValidFormRequest();
            $payload = $this->payload();
            $connection = (new Database())->connect();
            $identity = IdentityContext::current();
            $service = new UserProvisioningService(
                $connection,
                $identity,
                new AuditService(
                    new AuditLogRepository($connection),
                    $identity
                )
            );

            $name = (string) ($payload['name'] ?? '');
            $slug = (string) ($payload['slug'] ?? '');
            if (trim($name) === ''
                || preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', strtolower(trim($slug))) !== 1) {
                throw new DomainException('Nome e slug válido são obrigatórios.');
            }

            $logoPath = $this->storeCompanyLogo($_FILES['logo'] ?? null);

            $companyId = $service->createCompany(
                $name,
                $slug,
                $logoPath
            );

            return ['company_id' => $companyId];
        }, 'Empresa criada com sucesso.');
    }

    public function companiesIndex(): void
    {
        $this->startSession();
        $this->assertGovernanceAdmin();

        $connection = (new Database())->connect();
        $filters = $this->companyFilters();

        $this->setView('Admin/companies', [
            'title' => 'Empresas - Governança SaaS',
            'companies' => $this->companyDirectory($connection, $filters),
            'companyFilters' => $filters,
            'companiesCount' => $this->companiesCount($connection),
            'activeUsersCount' => $this->activeUsersCount($connection),
            'passwordResetUsersCount' => $this->passwordResetUsersCount($connection),
            'csrfToken' => $this->csrfToken(),
            'success' => $_SESSION['admin_success'] ?? null,
            'error' => $_SESSION['admin_error'] ?? null,
        ]);

        unset($_SESSION['admin_success'], $_SESSION['admin_error']);
    }

    public function updateCompany(): void
    {
        $this->executeCompanyAction(function (\PDO $connection, array $payload): void {
            $companyId = (int) ($payload['company_id'] ?? 0);
            $name = trim((string) ($payload['name'] ?? ''));
            $slug = strtolower(trim((string) ($payload['slug'] ?? '')));
            $logoPath = $this->storeCompanyLogo($_FILES['logo'] ?? null);

            if ($companyId <= 0
                || $name === ''
                || preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) !== 1) {
                throw new DomainException('Empresa, nome e slug válido são obrigatórios.');
            }

            $connection->beginTransaction();

            try {
                $company = $this->companyForUpdate($connection, $companyId);
                if ($company === null || $company['deleted_at'] !== null) {
                    throw new DomainException('Empresa indisponível para edição.');
                }

                $this->assertCompanySlugAvailable($connection, $slug, $companyId);

                $statement = $connection->prepare(
                    'UPDATE companies
                     SET name = :name,
                         slug = :slug,
                         logo_path = COALESCE(:logo_path, logo_path),
                         updated_at = NOW(6)
                     WHERE id = :company_id'
                );
                $statement->execute([
                    'name' => $name,
                    'slug' => $slug,
                    'logo_path' => $logoPath,
                    'company_id' => $companyId,
                ]);

                $this->auditCompany($connection, 'UPDATE', $companyId, [
                    'company_id' => $companyId,
                    'old_name' => $company['name'],
                    'old_slug' => $company['slug'],
                    'new_name' => $name,
                    'new_slug' => $slug,
                    'new_logo_path' => $logoPath ?? $company['logo_path'],
                ]);

                $connection->commit();
            } catch (Throwable $throwable) {
                if ($connection->inTransaction()) {
                    $connection->rollBack();
                }

                throw $throwable;
            }
        }, 'Empresa atualizada com sucesso.');
    }

    public function companyPricing(?int $routeCompanyId = null): void
    {
        $this->startSession();
        $this->assertGovernanceAdmin();

        $companyId = filter_var(
            $routeCompanyId ?? $_GET['company_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        if ($companyId === false) {
            $this->redirectCompanies();
        }

        $connection = (new Database())->connect();
        $statement = $connection->prepare(
            'SELECT id, name, legal_name, trade_name, cnpj, slug, logo_path, is_mensalista, deleted_at
             FROM companies
             WHERE id = :company_id AND deleted_at IS NULL
             LIMIT 1'
        );
        $statement->execute(['company_id' => (int) $companyId]);
        $company = $statement->fetch(\PDO::FETCH_ASSOC);
        if ($company === false) {
            $this->redirectCompanies();
        }

        $tariffsStatement = $connection->prepare(
            'SELECT tipo_veiculo, valor_base, valor_adicional,
                    tolerancia_minutos, frequencia_adicional
             FROM tarifarios
             WHERE company_id = :company_id
             ORDER BY FIELD(tipo_veiculo, "carro", "moto", "caminhao", "app")'
        );
        $tariffsStatement->execute(['company_id' => (int) $companyId]);
        $tariffs = [];
        foreach ($tariffsStatement->fetchAll(\PDO::FETCH_ASSOC) as $tariff) {
            $tariffs[(string) $tariff['tipo_veiculo']] = $tariff;
        }

        $this->setView('Admin/company-pricing', [
            'title' => 'Cobrança de ' . $company['name'],
            'company' => $company,
            'tariffs' => $tariffs,
            'monthlyContracts' => (new MonthlyContractRepository($connection))
                ->listForCompany((int) $companyId),
            'csrfToken' => $this->csrfToken(),
            'success' => $_SESSION['pricing_success'] ?? null,
            'error' => $_SESSION['pricing_error'] ?? null,
        ]);
        unset($_SESSION['pricing_success'], $_SESSION['pricing_error']);
    }

    public function updateCompanyPricing(?int $routeCompanyId = null): void
    {
        $this->startSession();
        $companyId = (int) ($routeCompanyId ?? $_POST['company_id'] ?? 0);

        if (!$this->hasValidAdminCsrf()) {
            $_SESSION['pricing_error'] = 'A sessão expirou. Tente novamente.';
            $this->redirectCompanyPricing($companyId);
        }

        try {
            $this->assertGovernanceAdmin();
            if ($companyId < 1) {
                throw new DomainException('Empresa inválida.');
            }

            $billingModel = (new InputSanitizer())->text($_POST['billing_model'] ?? '', 20);
            if (!in_array($billingModel, ['monthly', 'rotating'], true)) {
                throw new DomainException('Selecione um modelo de cobrança válido.');
            }
            $isMonthly = $billingModel === 'monthly';
            $sanitizer = new InputSanitizer();
            $legalName = $sanitizer->text($_POST['legal_name'] ?? '', 255);
            $tradeName = $sanitizer->text($_POST['trade_name'] ?? '', 255);
            $slug = strtolower($sanitizer->text($_POST['slug'] ?? '', 120));
            if ($tradeName === '') {
                throw new DomainException('O nome fantasia é obrigatório.');
            }
            if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) !== 1) {
                throw new DomainException('Informe um slug válido usando letras minúsculas, números e hífens.');
            }
            $logoPath = $this->storeCompanyLogo($_FILES['logo'] ?? null);
            $tariffPayload = is_array($_POST['tariffs'] ?? null) ? $_POST['tariffs'] : [];
            $vehicleTypes = ['carro', 'moto', 'caminhao', 'app'];
            $validatedTariffs = [];

            foreach ($vehicleTypes as $vehicleType) {
                $row = is_array($tariffPayload[$vehicleType] ?? null)
                    ? $tariffPayload[$vehicleType]
                    : [];

                $validatedTariffs[$vehicleType] = $this->validateTariff($vehicleType, $row);
            }

            if (count($validatedTariffs) !== count($vehicleTypes)) {
                throw new DomainException('Configure todos os tipos de veículo para receber veículos avulsos.');
            }

            $connection = (new Database())->connect();
            $connection->beginTransaction();
            try {
                $company = $this->companyForUpdate($connection, $companyId);
                if ($company === null || $company['deleted_at'] !== null) {
                    throw new DomainException('Empresa indisponível para configuração.');
                }
                $this->assertCompanySlugAvailable($connection, $slug, $companyId);

                $updateCompany = $connection->prepare(
                    'UPDATE companies
                     SET name = :display_name,
                         legal_name = :legal_name,
                         trade_name = :trade_name,
                         slug = :slug,
                         logo_path = COALESCE(:logo_path, logo_path),
                         is_mensalista = :is_mensalista,
                         updated_at = NOW(6)
                     WHERE id = :company_id'
                );
                $updateCompany->execute([
                    'display_name' => $tradeName,
                    'legal_name' => $legalName !== '' ? $legalName : null,
                    'trade_name' => $tradeName,
                    'slug' => $slug,
                    'logo_path' => $logoPath,
                    'is_mensalista' => $isMonthly ? 1 : 0,
                    'company_id' => $companyId,
                ]);

                $upsert = $connection->prepare(
                    'INSERT INTO tarifarios (
                        company_id, tipo_veiculo, valor_base, valor_adicional,
                        tolerancia_minutos, frequencia_adicional
                     ) VALUES (
                        :company_id, :tipo_veiculo, :valor_base, :valor_adicional,
                        :tolerancia_minutos, :frequencia_adicional
                     )
                     ON DUPLICATE KEY UPDATE
                        valor_base = VALUES(valor_base),
                        valor_adicional = VALUES(valor_adicional),
                        tolerancia_minutos = VALUES(tolerancia_minutos),
                        frequencia_adicional = VALUES(frequencia_adicional)'
                );
                foreach ($validatedTariffs as $vehicleType => $tariff) {
                    $upsert->execute(['company_id' => $companyId] + $tariff);
                }

                $this->auditCompany($connection, 'UPDATE', $companyId, [
                    'event' => 'COMPANY_PRICING_UPDATED',
                    'legal_name' => $legalName,
                    'trade_name' => $tradeName,
                    'slug' => $slug,
                    'logo_path' => $logoPath ?? $company['logo_path'],
                    'billing_model' => $isMonthly ? 'monthly' : 'rotating',
                    'tariffs' => $validatedTariffs,
                ]);
                $connection->commit();
            } catch (Throwable $throwable) {
                if ($connection->inTransaction()) {
                    $connection->rollBack();
                }
                throw $throwable;
            }

            $_SESSION['pricing_success'] = 'Modelo de cobrança e tarifários atualizados.';
        } catch (DomainException $exception) {
            $_SESSION['pricing_error'] = $exception->getMessage();
        } catch (Throwable $throwable) {
            error_log($throwable->getMessage());
            $_SESSION['pricing_error'] = 'Não foi possível atualizar a configuração de cobrança.';
        }

        $this->redirectCompanyPricing($companyId);
    }

    public function createMonthlyContract(int $companyId): void
    {
        $this->handleMonthlyContractRequest($companyId, function (\PDO $connection) use ($companyId): void {
            $data = $this->monthlyContractPayload();
            $userId = IdentityContext::current()->userId();
            $end = (new DateTimeImmutable($data['starts_at']))->modify('+1 month -1 day');

            $statement = $connection->prepare(
                'INSERT INTO monthly_contracts (
                    company_id, customer_name, vehicle_plate, vehicle_type, monthly_amount,
                    starts_at, expires_at, status, created_by, updated_by
                 ) VALUES (
                    :company_id, :customer_name, :vehicle_plate, :vehicle_type, :monthly_amount,
                    :starts_at, :expires_at, "ACTIVE", :created_by, :updated_by
                 )'
            );
            $statement->execute([
                'company_id' => $companyId,
                'customer_name' => $data['customer_name'],
                'vehicle_plate' => $data['vehicle_plate'],
                'vehicle_type' => $data['vehicle_type'],
                'monthly_amount' => $data['monthly_amount'],
                'starts_at' => $data['starts_at'],
                'expires_at' => $end->format('Y-m-d'),
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
            $contractId = (int) $connection->lastInsertId();
            $this->insertMonthlyPayment(
                $connection,
                $companyId,
                $contractId,
                $data['monthly_amount'],
                $data['payment_method'],
                $data['starts_at'],
                $end->format('Y-m-d')
            );
            $this->auditCompany($connection, 'CREATE', $companyId, [
                'event' => 'MONTHLY_CONTRACT_CREATED',
                'contract_id' => $contractId,
                'vehicle_plate' => $data['vehicle_plate'],
                'period_end' => $end->format('Y-m-d'),
            ]);
        }, 'Contrato mensalista criado e primeira mensalidade registrada.');
    }

    public function renewMonthlyContract(int $companyId): void
    {
        $this->handleMonthlyContractRequest($companyId, function (\PDO $connection) use ($companyId): void {
            $contractId = filter_var($_POST['contract_id'] ?? null, FILTER_VALIDATE_INT);
            $repository = new MonthlyContractRepository($connection);
            $contract = $contractId ? $repository->findForUpdate($companyId, (int) $contractId) : null;
            if ($contract === null) {
                throw new DomainException('Contrato mensalista inválido.');
            }

            $amount = $this->positiveMoney($_POST['monthly_amount'] ?? $contract['monthly_amount']);
            $method = $this->paymentMethod($_POST['payment_method'] ?? '');
            $today = new DateTimeImmutable('today');
            $afterCurrentPeriod = (new DateTimeImmutable((string) $contract['expires_at']))->modify('+1 day');
            $start = $afterCurrentPeriod > $today ? $afterCurrentPeriod : $today;
            $end = $start->modify('+1 month -1 day');

            $update = $connection->prepare(
                'UPDATE monthly_contracts
                 SET monthly_amount = :amount, starts_at = :starts_at, expires_at = :expires_at,
                     status = "ACTIVE", updated_by = :updated_by
                 WHERE id = :contract_id AND company_id = :company_id'
            );
            $update->execute([
                'amount' => $amount,
                'starts_at' => $start->format('Y-m-d'),
                'expires_at' => $end->format('Y-m-d'),
                'updated_by' => IdentityContext::current()->userId(),
                'contract_id' => $contractId,
                'company_id' => $companyId,
            ]);
            $this->insertMonthlyPayment($connection, $companyId, (int) $contractId, $amount, $method, $start->format('Y-m-d'), $end->format('Y-m-d'));
            $this->auditCompany($connection, 'UPDATE', $companyId, [
                'event' => 'MONTHLY_CONTRACT_RENEWED',
                'contract_id' => (int) $contractId,
                'period_start' => $start->format('Y-m-d'),
                'period_end' => $end->format('Y-m-d'),
            ]);
        }, 'Contrato renovado e pagamento registrado.');
    }

    public function cancelMonthlyContract(int $companyId): void
    {
        $this->handleMonthlyContractRequest($companyId, function (\PDO $connection) use ($companyId): void {
            $contractId = filter_var($_POST['contract_id'] ?? null, FILTER_VALIDATE_INT);
            $contract = $contractId
                ? (new MonthlyContractRepository($connection))->findForUpdate($companyId, (int) $contractId)
                : null;
            if ($contract === null) {
                throw new DomainException('Contrato mensalista inválido.');
            }
            $statement = $connection->prepare(
                'UPDATE monthly_contracts SET status = "CANCELLED", updated_by = :updated_by
                 WHERE id = :contract_id AND company_id = :company_id'
            );
            $statement->execute([
                'updated_by' => IdentityContext::current()->userId(),
                'contract_id' => $contractId,
                'company_id' => $companyId,
            ]);
            $this->auditCompany($connection, 'UPDATE', $companyId, [
                'event' => 'MONTHLY_CONTRACT_CANCELLED',
                'contract_id' => (int) $contractId,
            ]);
        }, 'Contrato mensalista cancelado.');
    }

    private function validateTariff(string $vehicleType, array $row): array
    {
        $sanitizer = new InputSanitizer();
        $base = $sanitizer->text($row['valor_base'] ?? '', 20);
        $additional = $sanitizer->text($row['valor_adicional'] ?? '', 20);
        $tolerance = filter_var($row['tolerancia_minutos'] ?? null, FILTER_VALIDATE_INT);
        $frequency = filter_var($row['frequencia_adicional'] ?? null, FILTER_VALIDATE_INT);

        if (preg_match('/^\d{1,8}(?:[.,]\d{1,2})?$/', $base) !== 1
            || preg_match('/^\d{1,8}(?:[.,]\d{1,2})?$/', $additional) !== 1
            || $tolerance === false
            || $tolerance < 0
            || $frequency === false
            || $frequency < 1
        ) {
            throw new DomainException('Tarifário inválido para ' . $vehicleType . '.');
        }

        return [
            'tipo_veiculo' => $vehicleType,
            'valor_base' => str_replace(',', '.', $base),
            'valor_adicional' => str_replace(',', '.', $additional),
            'tolerancia_minutos' => (int) $tolerance,
            'frequencia_adicional' => (int) $frequency,
        ];
    }

    public function deactivateCompany(): void
    {
        $this->executeCompanyAction(function (\PDO $connection, array $payload): void {
            $companyId = (int) ($payload['company_id'] ?? 0);
            if ($companyId <= 0) {
                throw new DomainException('Empresa inválida.');
            }

            $connection->beginTransaction();

            try {
                $company = $this->companyForUpdate($connection, $companyId);
                if ($company === null || $company['deleted_at'] !== null) {
                    throw new DomainException('Empresa indisponível para inativação.');
                }

                if ($this->isCurrentUsersLastActiveCompany($connection, $companyId)) {
                    throw new DomainException(
                        'Você não pode inativar sua última empresa ativa.'
                    );
                }

                $linkedUserIds = $this->linkedUserIdsForCompany($connection, $companyId);
                $statement = $connection->prepare(
                    'UPDATE companies
                     SET deleted_at = UTC_TIMESTAMP(6), updated_at = NOW(6)
                     WHERE id = :company_id AND deleted_at IS NULL'
                );
                $statement->execute(['company_id' => $companyId]);

                $deleteMemberships = $connection->prepare(
                    'DELETE FROM company_user WHERE company_id = :company_id'
                );
                $deleteMemberships->execute(['company_id' => $companyId]);

                $revokedUserIds = $this->revokeUsersWithoutActiveCompanies(
                    $connection,
                    $linkedUserIds
                );

                $this->auditCompany($connection, 'DELETE', $companyId, [
                    'company_id' => $companyId,
                    'company_name' => $company['name'],
                    'company_slug' => $company['slug'],
                    'revoked_company_memberships' => count($linkedUserIds),
                    'revoked_user_ids' => $revokedUserIds,
                    'message' => 'Empresa inativada e vínculos do tenant revogados.',
                ]);

                $connection->commit();
            } catch (Throwable $throwable) {
                if ($connection->inTransaction()) {
                    $connection->rollBack();
                }

                throw $throwable;
            }
        }, 'Empresa inativada e acessos do tenant revogados.');
    }

    private function userForPasswordReset(\PDO $connection, int $userId): ?array
    {
        $statement = $connection->prepare(
            'SELECT id_usuario, id_login, email, deleted_at
             FROM usuario
             WHERE id_usuario = :user_id
             LIMIT 1'
        );
        $statement->execute(['user_id' => $userId]);
        $user = $statement->fetch(\PDO::FETCH_ASSOC);

        return $user === false ? null : $user;
    }

    private function companies(\PDO $connection): array
    {
        return $connection->query(
            'SELECT
                c.id, c.name, c.slug, c.logo_path, COUNT(cu.user_id) AS users_count
             FROM companies c
             LEFT JOIN company_user cu ON cu.company_id = c.id
             WHERE c.deleted_at IS NULL
             GROUP BY c.id, c.name, c.slug, c.logo_path
             ORDER BY c.name ASC'
        )->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function permissions(\PDO $connection): array
    {
        return $connection->query(
            'SELECT id, slug, name
             FROM permissions
             WHERE is_active = 1
             ORDER BY name ASC, slug ASC'
        )->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function activeUsers(\PDO $connection): array
    {
        return $connection->query(
            'SELECT
                u.id_usuario,
                u.nome,
                u.email,
                GROUP_CONCAT(
                    DISTINCT CONCAT(c.id, \'::\', c.name, \'::\', c.slug)
                    ORDER BY c.name
                    SEPARATOR \'||\'
                ) AS company_access
             FROM usuario u
             LEFT JOIN (
                SELECT user_id, company_id, MAX(role_id) AS role_id
                FROM company_user
                GROUP BY user_id, company_id
             ) cu ON cu.user_id = u.id_usuario
             LEFT JOIN roles r ON r.id = cu.role_id AND r.is_active = 1
             LEFT JOIN companies c ON c.id = cu.company_id AND c.deleted_at IS NULL
                AND r.id IS NOT NULL
             WHERE u.deleted_at IS NULL
             GROUP BY u.id_usuario, u.nome, u.email
             ORDER BY u.nome ASC, u.email ASC'
        )->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function companyDirectory(\PDO $connection, array $filters): array
    {
        $where = [];
        $parameters = [];

        if ($filters['query'] !== '') {
            $where[] = '(c.name LIKE :company_name_query OR c.slug LIKE :company_slug_query)';
            $parameters['company_name_query'] = '%' . $filters['query'] . '%';
            $parameters['company_slug_query'] = '%' . $filters['query'] . '%';
        }

        if ($filters['status'] === 'active') {
            $where[] = 'c.deleted_at IS NULL';
        } elseif ($filters['status'] === 'inactive') {
            $where[] = 'c.deleted_at IS NOT NULL';
        }

        $whereSql = $where === [] ? '' : 'WHERE ' . implode(' AND ', $where);
        $limit = $filters['is_filtered'] ? 50 : 12;
        $statement = $connection->prepare(
            'SELECT
                c.id,
                c.name,
                c.slug,
                c.logo_path,
                c.deleted_at,
                COUNT(DISTINCT cu.user_id) AS users_count,
                SUM(CASE WHEN u.deleted_at IS NULL THEN 1 ELSE 0 END) AS active_users_count,
                SUM(CASE WHEN u.deleted_at IS NULL AND u.password_reset_required = 1 THEN 1 ELSE 0 END) AS password_reset_users_count,
                GROUP_CONCAT(DISTINCT r.slug ORDER BY r.slug SEPARATOR \', \') AS role_slugs
             FROM companies c
             LEFT JOIN company_user cu ON cu.company_id = c.id
             LEFT JOIN usuario u ON u.id_usuario = cu.user_id
             LEFT JOIN roles r ON r.id = cu.role_id
             ' . $whereSql . '
             GROUP BY c.id, c.name, c.slug, c.logo_path, c.deleted_at
             ORDER BY c.name ASC, c.id ASC
             LIMIT ' . $limit
        );
        foreach ($parameters as $key => $value) {
            $statement->bindValue(':' . $key, $value, \PDO::PARAM_STR);
        }
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function companiesCount(\PDO $connection): int
    {
        return (int) $connection
            ->query('SELECT COUNT(*) FROM companies')
            ->fetchColumn();
    }

    private function assignableRoles(\PDO $connection): array
    {
        $roles = IdentityContext::current()->roleSlugs();
        $canAssignPrivileged = in_array('super-admin', $roles, true);
        $where = $canAssignPrivileged
            ? 'WHERE is_active = 1'
            : "WHERE is_active = 1 AND slug NOT IN ('master', 'super-admin')";

        return $connection->query(
            'SELECT id, slug, name, label
             FROM roles ' . $where . '
             ORDER BY display_priority ASC, name ASC'
        )->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function users(\PDO $connection, array $filters): array
    {
        $where = ['u.deleted_at IS NULL'];
        $parameters = [];

        if ($filters['query'] !== '') {
            $where[] = '(u.nome LIKE :user_name_query OR u.email LIKE :user_email_query)';
            $parameters['user_name_query'] = '%' . $filters['query'] . '%';
            $parameters['user_email_query'] = '%' . $filters['query'] . '%';
        }

        if ($filters['company_id'] !== null) {
            $where[] = 'cu.company_id = :company_id';
            $parameters['company_id'] = $filters['company_id'];
        }

        if ($filters['password_reset_required']) {
            $where[] = 'u.password_reset_required = 1';
        }

        $limit = $filters['is_filtered'] ? 20 : 3;
        $statement = $connection->prepare(
            'SELECT
                u.id_usuario,
                u.nome,
                u.email,
                u.password_reset_required,
                GROUP_CONCAT(DISTINCT c.name ORDER BY c.name SEPARATOR \', \') AS company_name,
                GROUP_CONCAT(DISTINCT c.slug ORDER BY c.slug SEPARATOR \', \') AS company_slug,
                GROUP_CONCAT(DISTINCT r.slug ORDER BY r.slug SEPARATOR \', \') AS role_slug,
                GROUP_CONCAT(
                    DISTINCT CONCAT(
                        c.id,
                        \'::\',
                        c.name,
                        \'::\',
                        COALESCE(r.slug, \'sem papel\'),
                        \'::\',
                        COALESCE(extra_permissions.permission_ids, \'\'),
                        \'::\',
                        COALESCE(r.label, r.name, r.slug, \'Sem papel\')
                    )
                    ORDER BY c.name
                    SEPARATOR \'||\'
                ) AS company_access
             FROM usuario u
             LEFT JOIN (
                SELECT user_id, company_id, MAX(role_id) AS role_id
                FROM company_user
                GROUP BY user_id, company_id
             ) cu ON cu.user_id = u.id_usuario
             LEFT JOIN companies c ON c.id = cu.company_id
             LEFT JOIN roles r ON r.id = cu.role_id
             LEFT JOIN (
                SELECT
                    user_id,
                    company_id,
                    GROUP_CONCAT(permission_id ORDER BY permission_id SEPARATOR \',\') AS permission_ids
                FROM company_user_permissions
                GROUP BY user_id, company_id
             ) extra_permissions
                ON extra_permissions.user_id = u.id_usuario
               AND extra_permissions.company_id = c.id
             WHERE ' . implode(' AND ', $where) . '
             GROUP BY u.id_usuario, u.nome, u.email, u.password_reset_required
             ORDER BY u.id_usuario DESC
             LIMIT ' . $limit
        );
        foreach ($parameters as $key => $value) {
            $statement->bindValue(
                ':' . $key,
                $value,
                $key === 'company_id' ? \PDO::PARAM_INT : \PDO::PARAM_STR
            );
        }
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function activeUsersCount(\PDO $connection): int
    {
        return (int) $connection
            ->query('SELECT COUNT(*) FROM usuario WHERE deleted_at IS NULL')
            ->fetchColumn();
    }

    private function syncTenantPermissionOverrides(
        \PDO $connection,
        int $userId,
        int $companyId,
        array $permissionIds
    ): void {
        if ($userId <= 0 || $companyId <= 0) {
            throw new DomainException('Selecione usuário e empresa válidos.');
        }

        $membership = $connection->prepare(
            'SELECT 1
             FROM company_user cu
             INNER JOIN usuario u ON u.id_usuario = cu.user_id AND u.deleted_at IS NULL
             INNER JOIN companies c ON c.id = cu.company_id AND c.deleted_at IS NULL
             WHERE cu.user_id = :user_id
               AND cu.company_id = :company_id
               AND cu.role_id IS NOT NULL
             LIMIT 1'
        );
        $membership->execute([
            'user_id' => $userId,
            'company_id' => $companyId,
        ]);

        if ($membership->fetchColumn() === false) {
            throw new DomainException('Este usuário não possui vínculo ativo com a empresa selecionada.');
        }

        $permissionIds = array_values(array_unique(array_filter(
            array_map('intval', $permissionIds),
            static fn (int $permissionId): bool => $permissionId > 0
        )));
        $before = $this->companyPermissionIds($connection, $userId, $companyId);

        $connection->beginTransaction();
        try {
            $delete = $connection->prepare(
                'DELETE FROM company_user_permissions
                 WHERE user_id = :user_id AND company_id = :company_id'
            );
            $delete->execute([
                'user_id' => $userId,
                'company_id' => $companyId,
            ]);

            $insert = $connection->prepare(
                'INSERT INTO company_user_permissions (
                    user_id, company_id, permission_id, granted_by
                 )
                 SELECT :user_id, :company_id, id, :granted_by
                 FROM permissions
                 WHERE id = :permission_id AND is_active = 1'
            );

            foreach ($permissionIds as $permissionId) {
                $insert->execute([
                    'user_id' => $userId,
                    'company_id' => $companyId,
                    'permission_id' => $permissionId,
                    'granted_by' => IdentityContext::current()->userId(),
                ]);
            }

            $after = $this->companyPermissionIds($connection, $userId, $companyId);
            $this->auditCompany($connection, 'USER_PERMISSIONS_UPDATED', $companyId, [
                'scope' => 'company_user',
                'target_user_id' => $userId,
                'target_company_id' => $companyId,
                'permissions_added' => $this->permissionSlugs(
                    $connection,
                    array_values(array_diff($after, $before))
                ),
                'permissions_removed' => $this->permissionSlugs(
                    $connection,
                    array_values(array_diff($before, $after))
                ),
            ]);

            $connection->commit();
        } catch (Throwable $throwable) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            throw $throwable;
        }
    }

    private function companyPermissionIds(\PDO $connection, int $userId, int $companyId): array
    {
        $statement = $connection->prepare(
            'SELECT permission_id
             FROM company_user_permissions
             WHERE user_id = :user_id AND company_id = :company_id'
        );
        $statement->execute([
            'user_id' => $userId,
            'company_id' => $companyId,
        ]);

        return array_map('intval', $statement->fetchAll(\PDO::FETCH_COLUMN));
    }

    private function permissionSlugs(\PDO $connection, array $permissionIds): array
    {
        if ($permissionIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($permissionIds), '?'));
        $statement = $connection->prepare(
            'SELECT slug
             FROM permissions
             WHERE id IN (' . $placeholders . ')
             ORDER BY slug ASC'
        );
        $statement->execute(array_values($permissionIds));

        return $statement->fetchAll(\PDO::FETCH_COLUMN);
    }

    private function passwordResetUsersCount(\PDO $connection): int
    {
        return (int) $connection
            ->query('SELECT COUNT(*) FROM usuario WHERE deleted_at IS NULL AND password_reset_required = 1')
            ->fetchColumn();
    }

    private function userFilters(): array
    {
        $query = trim((string) ($_GET['q'] ?? ''));
        $companyId = filter_var(
            $_GET['company_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        $passwordResetRequired = (string) ($_GET['password_reset_required'] ?? '') === '1';

        return [
            'query' => substr($query, 0, 120),
            'company_id' => $companyId === false ? null : (int) $companyId,
            'password_reset_required' => $passwordResetRequired,
            'is_filtered' => $query !== ''
                || $companyId !== false
                || $passwordResetRequired,
        ];
    }

    private function companyFilters(): array
    {
        $query = trim((string) ($_GET['q'] ?? ''));
        $status = (string) ($_GET['status'] ?? 'active');
        $status = in_array($status, ['active', 'inactive', 'all'], true)
            ? $status
            : 'active';

        return [
            'query' => substr($query, 0, 120),
            'status' => $status,
            'is_filtered' => $query !== ''
                || $status !== 'active',
        ];
    }

    private function executeCompanyAction(callable $action, string $successMessage): void
    {
        $this->startSession();

        if (!$this->hasValidAdminCsrf()) {
            $_SESSION['admin_error'] = 'A sessão expirou. Tente novamente.';
            $this->redirectCompanies();
        }

        $connection = (new Database())->connect();

        try {
            $this->assertGovernanceAdmin();
            $action($connection, $this->payload());
            $_SESSION['admin_success'] = $successMessage;
        } catch (DomainException $exception) {
            $_SESSION['admin_error'] = $exception->getMessage();
        } catch (Throwable $throwable) {
            error_log($throwable->getMessage());
            $_SESSION['admin_error'] = 'Não foi possível concluir a alteração da empresa.';
        }

        $this->redirectCompanies();
    }

    private function companyForUpdate(\PDO $connection, int $companyId): ?array
    {
        $statement = $connection->prepare(
            'SELECT id, name, slug, logo_path, deleted_at
             FROM companies
             WHERE id = :company_id
             FOR UPDATE'
        );
        $statement->execute(['company_id' => $companyId]);
        $company = $statement->fetch(\PDO::FETCH_ASSOC);

        return $company === false ? null : $company;
    }

    private function assertCompanySlugAvailable(
        \PDO $connection,
        string $slug,
        int $exceptCompanyId
    ): void {
        $statement = $connection->prepare(
            'SELECT 1 FROM companies
             WHERE slug = :slug AND id <> :company_id
             LIMIT 1'
        );
        $statement->execute([
            'slug' => $slug,
            'company_id' => $exceptCompanyId,
        ]);

        if ($statement->fetchColumn() !== false) {
            throw new DomainException('Já existe uma empresa com esse slug.');
        }
    }

    private function linkedUserIdsForCompany(\PDO $connection, int $companyId): array
    {
        $statement = $connection->prepare(
            'SELECT user_id FROM company_user WHERE company_id = :company_id'
        );
        $statement->execute(['company_id' => $companyId]);

        return array_map('intval', $statement->fetchAll(\PDO::FETCH_COLUMN));
    }

    private function revokeUsersWithoutActiveCompanies(
        \PDO $connection,
        array $userIds
    ): array {
        $revoked = [];
        $membershipCheck = $connection->prepare(
            'SELECT COUNT(*)
             FROM company_user cu
             INNER JOIN companies c ON c.id = cu.company_id AND c.deleted_at IS NULL
             WHERE cu.user_id = :user_id'
        );
        $revoke = $connection->prepare(
            'UPDATE usuario
             SET deleted_at = UTC_TIMESTAMP(6)
             WHERE id_usuario = :user_id AND deleted_at IS NULL'
        );

        foreach (array_values(array_unique($userIds)) as $userId) {
            if ($userId === IdentityContext::current()->userId()) {
                continue;
            }

            $membershipCheck->execute(['user_id' => $userId]);
            if ((int) $membershipCheck->fetchColumn() > 0) {
                continue;
            }

            $revoke->execute(['user_id' => $userId]);
            if ($revoke->rowCount() > 0) {
                $revoked[] = $userId;
            }
        }

        return $revoked;
    }

    private function isCurrentUsersLastActiveCompany(
        \PDO $connection,
        int $companyId
    ): bool {
        $statement = $connection->prepare(
            'SELECT COUNT(*)
             FROM company_user cu
             INNER JOIN companies c ON c.id = cu.company_id AND c.deleted_at IS NULL
             WHERE cu.user_id = :user_id'
        );
        $statement->execute(['user_id' => IdentityContext::current()->userId()]);
        $activeMemberships = (int) $statement->fetchColumn();

        $linkedToTarget = $connection->prepare(
            'SELECT 1 FROM company_user
             WHERE user_id = :user_id AND company_id = :company_id
             LIMIT 1'
        );
        $linkedToTarget->execute([
            'user_id' => IdentityContext::current()->userId(),
            'company_id' => $companyId,
        ]);

        return $activeMemberships <= 1 && $linkedToTarget->fetchColumn() !== false;
    }

    private function auditCompany(
        \PDO $connection,
        string $action,
        int $companyId,
        array $payload
    ): void {
        $identity = IdentityContext::current();
        (new AuditLogRepository($connection))->insert([
            'user_id' => $identity->userId(),
            'company_id' => $companyId,
            'actor_email' => $identity->email(),
            'action' => $action,
            'entity' => 'companies',
            'entity_id' => (string) $companyId,
            'old_values' => null,
            'new_values' => json_encode($payload, JSON_THROW_ON_ERROR),
            'ip_address' => $identity->ipAddress(),
            'request_id' => $identity->requestId(),
            'created_at' => (new DateTimeImmutable('now', new DateTimeZone('UTC')))
                ->format('Y-m-d H:i:s.u'),
        ]);
    }

    private function handleMonthlyContractRequest(
        int $companyId,
        callable $operation,
        string $successMessage
    ): never {
        $this->startSession();
        if (!$this->hasValidAdminCsrf()) {
            $_SESSION['pricing_error'] = 'A sessão expirou. Tente novamente.';
            $this->redirectCompanyPricing($companyId);
        }

        try {
            $this->assertGovernanceAdmin();
            if ($companyId < 1) {
                throw new DomainException('Empresa inválida.');
            }
            $connection = (new Database())->connect();
            $connection->beginTransaction();
            try {
                if ($this->companyForUpdate($connection, $companyId) === null) {
                    throw new DomainException('Empresa indisponível.');
                }
                $operation($connection);
                $connection->commit();
            } catch (Throwable $throwable) {
                if ($connection->inTransaction()) {
                    $connection->rollBack();
                }
                throw $throwable;
            }
            $_SESSION['pricing_success'] = $successMessage;
        } catch (DomainException $exception) {
            $_SESSION['pricing_error'] = $exception->getMessage();
        } catch (\PDOException $exception) {
            error_log($exception->getMessage());
            $_SESSION['pricing_error'] = str_contains($exception->getMessage(), 'uq_monthly_contract_company_plate')
                ? 'Já existe um contrato para esta placa. Renove o contrato existente.'
                : 'Não foi possível salvar o contrato mensalista.';
        } catch (Throwable $throwable) {
            error_log($throwable->getMessage());
            $_SESSION['pricing_error'] = 'Não foi possível salvar o contrato mensalista.';
        }

        $this->redirectCompanyPricing($companyId);
    }

    private function monthlyContractPayload(): array
    {
        $sanitizer = new InputSanitizer();
        $name = $sanitizer->text($_POST['customer_name'] ?? '', 120);
        $plate = strtoupper($sanitizer->text($_POST['vehicle_plate'] ?? '', 10));
        $plate = preg_replace('/[^A-Z0-9-]/', '', $plate) ?? '';
        $vehicleType = $sanitizer->text($_POST['vehicle_type'] ?? '', 30);
        $startsAt = $sanitizer->text($_POST['starts_at'] ?? '', 10);

        if ($name === '' || preg_match('/^[A-Z0-9]{3}-?[A-Z0-9]{4}$/', $plate) !== 1) {
            throw new DomainException('Informe o cliente e uma placa válida.');
        }
        if (!in_array($vehicleType, ['carro', 'moto', 'caminhao', 'app'], true)) {
            throw new DomainException('Selecione um tipo de veículo válido.');
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $startsAt);
        if ($date === false || $date->format('Y-m-d') !== $startsAt) {
            throw new DomainException('Informe uma data inicial válida.');
        }

        return [
            'customer_name' => $name,
            'vehicle_plate' => $plate,
            'vehicle_type' => $vehicleType,
            'monthly_amount' => $this->positiveMoney($_POST['monthly_amount'] ?? null),
            'starts_at' => $startsAt,
            'payment_method' => $this->paymentMethod($_POST['payment_method'] ?? ''),
        ];
    }

    private function positiveMoney(mixed $value): string
    {
        $normalized = str_replace(',', '.', trim((string) $value));
        if (!is_numeric($normalized) || (float) $normalized <= 0 || (float) $normalized > 99999999.99) {
            throw new DomainException('Informe um valor mensal válido.');
        }

        return number_format((float) $normalized, 2, '.', '');
    }

    private function paymentMethod(mixed $value): string
    {
        $method = strtoupper((new InputSanitizer())->text($value, 10));
        if (!in_array($method, ['PIX', 'CARD', 'CASH'], true)) {
            throw new DomainException('Selecione uma forma de pagamento válida.');
        }

        return $method;
    }

    private function insertMonthlyPayment(
        \PDO $connection,
        int $companyId,
        int $contractId,
        string $amount,
        string $method,
        string $periodStart,
        string $periodEnd
    ): void {
        $statement = $connection->prepare(
            'INSERT INTO monthly_contract_payments (
                company_id, contract_id, amount, payment_method,
                period_start, period_end, created_by
             ) VALUES (
                :company_id, :contract_id, :amount, :payment_method,
                :period_start, :period_end, :created_by
             )'
        );
        $statement->execute([
            'company_id' => $companyId,
            'contract_id' => $contractId,
            'amount' => $amount,
            'payment_method' => $method,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'created_by' => IdentityContext::current()->userId(),
        ]);
    }

    private function respond(callable $action, string $successMessage): void
    {
        if ($this->expectsJson()) {
            $this->jsonResponse($action);

            return;
        }

        $this->startSession();

        try {
            $this->assertGovernanceAdmin();
            $action();
            $_SESSION['admin_success'] = $successMessage;
        } catch (DomainException $exception) {
            $_SESSION['admin_error'] = $exception->getMessage();
        } catch (Throwable $throwable) {
            error_log($throwable->getMessage());
            $_SESSION['admin_error'] = 'Não foi possível concluir o provisionamento.';
        }

        $this->redirect();
    }

    private function jsonResponse(callable $action): void
    {
        header('Content-Type: application/json; charset=UTF-8');

        try {
            $this->assertGovernanceAdmin();
            http_response_code(201);
            echo json_encode($action(), JSON_THROW_ON_ERROR);
        } catch (ForbiddenException $exception) {
            http_response_code(403);
            echo json_encode(['error' => $exception->getMessage()]);
        } catch (DomainException $exception) {
            http_response_code(422);
            echo json_encode(['error' => $exception->getMessage()]);
        } catch (Throwable $throwable) {
            error_log($throwable->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Não foi possível concluir o provisionamento.']);
        }
    }

    private function expectsJson(): bool
    {
        $contentType = (string) ($_SERVER['CONTENT_TYPE'] ?? '');
        $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');

        return str_contains($contentType, 'application/json')
            || str_contains($accept, 'application/json');
    }

    private function assertValidFormRequest(): void
    {
        if ($this->expectsJson()) {
            return;
        }

        $this->startSession();

        if (!$this->hasValidAdminCsrf()) {
            throw new DomainException('A sessão expirou. Tente novamente.');
        }
    }

    private function hasValidAdminCsrf(): bool
    {
        return isset($_SESSION['admin_csrf'], $_POST['csrf_token'])
            && hash_equals($_SESSION['admin_csrf'], (string) $_POST['csrf_token']);
    }

    private function csrfToken(): string
    {
        if (!isset($_SESSION['admin_csrf'])) {
            $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['admin_csrf'];
    }

    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function redirect(): never
    {
        header('Location: /admin');
        exit;
    }

    private function redirectCompanies(): never
    {
        header('Location: /admin/companies');
        exit;
    }

    private function redirectCompanyPricing(int $companyId): never
    {
        if ($companyId < 1) {
            $this->redirectCompanies();
        }

        header('Location: /admin/companies/company_id=' . $companyId);
        exit;
    }

    private function assertGovernanceAdmin(): void
    {
        $roles = IdentityContext::current()->roleSlugs();
        if (!in_array('master', $roles, true)
            && !in_array('super-admin', $roles, true)
            && !in_array('admin', $roles, true)) {
            throw new ForbiddenException(
                'admin.provision',
                'Apenas usuários MASTER ou ADMIN podem provisionar acessos.'
            );
        }
    }

    private function payload(): array
    {
        $contentType = (string) ($_SERVER['CONTENT_TYPE'] ?? '');
        if (str_contains($contentType, 'application/json')) {
            $decoded = json_decode((string) file_get_contents('php://input'), true);

            return is_array($decoded) ? $decoded : [];
        }

        return $_POST;
    }

    private function storeCompanyLogo(?array $file): ?string
    {
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK
            || !is_uploaded_file((string) ($file['tmp_name'] ?? ''))
            || (int) ($file['size'] ?? 0) > 2 * 1024 * 1024) {
            throw new DomainException('Logo inválida ou maior que 2 MB.');
        }

        $allowedMimeTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];
        $mimeType = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);

        if (!isset($allowedMimeTypes[$mimeType])) {
            throw new DomainException('Formato inválido. Envie logo em JPG, PNG ou WEBP.');
        }

        $uploadDir = dirname(__DIR__, 2) . '/public/uploads/companies';
        if ((!is_dir($uploadDir) && !mkdir($uploadDir, 0750, true) && !is_dir($uploadDir))
            || !is_writable($uploadDir)) {
            throw new DomainException('Não foi possível preparar o diretório de logos.');
        }

        $fileName = sprintf(
            'company_%s.%s',
            bin2hex(random_bytes(16)),
            $allowedMimeTypes[$mimeType]
        );
        $destPath = $uploadDir . DIRECTORY_SEPARATOR . $fileName;

        if (!@move_uploaded_file($file['tmp_name'], $destPath)) {
            throw new DomainException('Não foi possível salvar a logo.');
        }

        return 'companies/' . $fileName;
    }
}
