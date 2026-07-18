<?php

declare(strict_types=1);

namespace App\Billing\Presentation;

use App\Context\IdentityContext;
use App\Exceptions\ForbiddenException;
use App\Billing\Infrastructure\MonthlyContractRepository;
use App\Repositories\AuditLogRepository;
use App\Security\InputSanitizer;
use Config\Database;
use Core\Controller;
use DateTimeImmutable;
use DateTimeZone;
use DomainException;
use PDO;
use PDOException;
use Throwable;

final class AdminMonthlyContractController extends Controller
{
    public function create(int $companyId): void
    {
        $this->handleRequest($companyId, function (PDO $connection) use ($companyId): void {
            $data = $this->contractPayload();
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

            $this->insertPayment(
                $connection,
                $companyId,
                $contractId,
                $data['monthly_amount'],
                $data['payment_method'],
                $data['starts_at'],
                $end->format('Y-m-d')
            );
            $this->audit($connection, 'CREATE', $companyId, [
                'event' => 'MONTHLY_CONTRACT_CREATED',
                'contract_id' => $contractId,
                'vehicle_plate' => $data['vehicle_plate'],
                'period_end' => $end->format('Y-m-d'),
            ]);
        }, 'Contrato mensalista criado e primeira mensalidade registrada.');
    }

    public function renew(int $companyId): void
    {
        $this->handleRequest($companyId, function (PDO $connection) use ($companyId): void {
            $contractId = filter_var($_POST['contract_id'] ?? null, FILTER_VALIDATE_INT);
            $contract = $contractId
                ? (new MonthlyContractRepository($connection))->findForUpdate($companyId, (int) $contractId)
                : null;
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
            $this->insertPayment(
                $connection,
                $companyId,
                (int) $contractId,
                $amount,
                $method,
                $start->format('Y-m-d'),
                $end->format('Y-m-d')
            );
            $this->audit($connection, 'UPDATE', $companyId, [
                'event' => 'MONTHLY_CONTRACT_RENEWED',
                'contract_id' => (int) $contractId,
                'period_start' => $start->format('Y-m-d'),
                'period_end' => $end->format('Y-m-d'),
            ]);
        }, 'Contrato renovado e pagamento registrado.');
    }

    public function cancel(int $companyId): void
    {
        $this->handleRequest($companyId, function (PDO $connection) use ($companyId): void {
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
            $this->audit($connection, 'UPDATE', $companyId, [
                'event' => 'MONTHLY_CONTRACT_CANCELLED',
                'contract_id' => (int) $contractId,
            ]);
        }, 'Contrato mensalista cancelado.');
    }

    private function handleRequest(int $companyId, callable $operation, string $successMessage): never
    {
        $this->startSession();
        if (!$this->hasValidCsrf()) {
            $_SESSION['pricing_error'] = 'A sessão expirou. Tente novamente.';
            $this->redirect($companyId);
        }

        try {
            $this->assertGovernanceAdmin();
            if ($companyId < 1) {
                throw new DomainException('Empresa inválida.');
            }

            $connection = (new Database())->connect();
            $connection->beginTransaction();
            try {
                if (!$this->companyExistsForUpdate($connection, $companyId)) {
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
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            $_SESSION['pricing_error'] = str_contains($exception->getMessage(), 'uq_monthly_contract_company_plate')
                ? 'Já existe um contrato para esta placa. Renove o contrato existente.'
                : 'Não foi possível salvar o contrato mensalista.';
        } catch (Throwable $throwable) {
            error_log($throwable->getMessage());
            $_SESSION['pricing_error'] = 'Não foi possível salvar o contrato mensalista.';
        }

        $this->redirect($companyId);
    }

    private function contractPayload(): array
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

    private function insertPayment(
        PDO $connection,
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

    private function companyExistsForUpdate(PDO $connection, int $companyId): bool
    {
        $statement = $connection->prepare(
            'SELECT 1 FROM companies WHERE id = :company_id AND deleted_at IS NULL FOR UPDATE'
        );
        $statement->execute(['company_id' => $companyId]);

        return $statement->fetchColumn() !== false;
    }

    private function audit(PDO $connection, string $action, int $companyId, array $payload): void
    {
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

    private function hasValidCsrf(): bool
    {
        return isset($_SESSION['admin_csrf'], $_POST['csrf_token'])
            && hash_equals($_SESSION['admin_csrf'], (string) $_POST['csrf_token']);
    }

    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function redirect(int $companyId): never
    {
        if ($companyId < 1) {
            header('Location: /admin/companies');
            exit;
        }

        header('Location: /admin/companies/company_id=' . $companyId);
        exit;
    }
}

