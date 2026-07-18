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
use Throwable;

final class AdminCompanyBillingController extends Controller
{
    public function show(?int $routeCompanyId = null): void
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
             FROM companies WHERE id = :company_id AND deleted_at IS NULL LIMIT 1'
        );
        $statement->execute(['company_id' => (int) $companyId]);
        $company = $statement->fetch(PDO::FETCH_ASSOC);
        if ($company === false) {
            $this->redirectCompanies();
        }

        $statement = $connection->prepare(
            'SELECT tipo_veiculo, valor_base, valor_adicional,
                    tolerancia_minutos, frequencia_adicional
             FROM tarifarios WHERE company_id = :company_id
             ORDER BY FIELD(tipo_veiculo, "carro", "moto", "caminhao", "app")'
        );
        $statement->execute(['company_id' => (int) $companyId]);
        $tariffs = [];
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $tariff) {
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

    public function update(?int $routeCompanyId = null): void
    {
        $this->startSession();
        $companyId = (int) ($routeCompanyId ?? $_POST['company_id'] ?? 0);
        if (!$this->hasValidCsrf()) {
            $_SESSION['pricing_error'] = 'A sessão expirou. Tente novamente.';
            $this->redirect($companyId);
        }

        try {
            $this->assertGovernanceAdmin();
            if ($companyId < 1) {
                throw new DomainException('Empresa inválida.');
            }
            $data = $this->validatedPayload();
            $connection = (new Database())->connect();
            $connection->beginTransaction();
            try {
                $company = $this->companyForUpdate($connection, $companyId);
                if ($company === null || $company['deleted_at'] !== null) {
                    throw new DomainException('Empresa indisponível para configuração.');
                }
                $this->assertSlugAvailable($connection, $data['slug'], $companyId);
                $connection->prepare(
                    'UPDATE companies SET name = :trade_name, legal_name = :legal_name,
                     trade_name = :trade_name, slug = :slug,
                     logo_path = COALESCE(:logo_path, logo_path),
                     is_mensalista = :is_mensalista, updated_at = NOW(6)
                     WHERE id = :company_id'
                )->execute([
                    'trade_name' => $data['trade_name'],
                    'legal_name' => $data['legal_name'] !== '' ? $data['legal_name'] : null,
                    'slug' => $data['slug'],
                    'logo_path' => $data['logo_path'],
                    'is_mensalista' => $data['is_monthly'] ? 1 : 0,
                    'company_id' => $companyId,
                ]);

                $upsert = $connection->prepare(
                    'INSERT INTO tarifarios (company_id, tipo_veiculo, valor_base, valor_adicional,
                     tolerancia_minutos, frequencia_adicional)
                     VALUES (:company_id, :tipo_veiculo, :valor_base, :valor_adicional,
                     :tolerancia_minutos, :frequencia_adicional)
                     ON DUPLICATE KEY UPDATE valor_base = VALUES(valor_base),
                     valor_adicional = VALUES(valor_adicional),
                     tolerancia_minutos = VALUES(tolerancia_minutos),
                     frequencia_adicional = VALUES(frequencia_adicional)'
                );
                foreach ($data['tariffs'] as $tariff) {
                    $upsert->execute(['company_id' => $companyId] + $tariff);
                }
                $this->audit($connection, $companyId, $data, $company);
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
        $this->redirect($companyId);
    }

    private function validatedPayload(): array
    {
        $sanitizer = new InputSanitizer();
        $billingModel = $sanitizer->text($_POST['billing_model'] ?? '', 20);
        if (!in_array($billingModel, ['monthly', 'rotating'], true)) {
            throw new DomainException('Selecione um modelo de cobrança válido.');
        }
        $tradeName = $sanitizer->text($_POST['trade_name'] ?? '', 255);
        $slug = strtolower($sanitizer->text($_POST['slug'] ?? '', 120));
        if ($tradeName === '') {
            throw new DomainException('O nome fantasia é obrigatório.');
        }
        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) !== 1) {
            throw new DomainException('Informe um slug válido usando letras minúsculas, números e hífens.');
        }
        $payload = is_array($_POST['tariffs'] ?? null) ? $_POST['tariffs'] : [];
        $tariffs = [];
        foreach (['carro', 'moto', 'caminhao', 'app'] as $type) {
            $tariffs[$type] = $this->validateTariff(
                $type,
                is_array($payload[$type] ?? null) ? $payload[$type] : []
            );
        }

        return [
            'legal_name' => $sanitizer->text($_POST['legal_name'] ?? '', 255),
            'trade_name' => $tradeName,
            'slug' => $slug,
            'logo_path' => $this->storeLogo($_FILES['logo'] ?? null),
            'is_monthly' => $billingModel === 'monthly',
            'tariffs' => $tariffs,
        ];
    }

    private function validateTariff(string $type, array $row): array
    {
        $sanitizer = new InputSanitizer();
        $base = $sanitizer->text($row['valor_base'] ?? '', 20);
        $additional = $sanitizer->text($row['valor_adicional'] ?? '', 20);
        $tolerance = filter_var($row['tolerancia_minutos'] ?? null, FILTER_VALIDATE_INT);
        $frequency = filter_var($row['frequencia_adicional'] ?? null, FILTER_VALIDATE_INT);
        if (preg_match('/^\d{1,8}(?:[.,]\d{1,2})?$/', $base) !== 1
            || preg_match('/^\d{1,8}(?:[.,]\d{1,2})?$/', $additional) !== 1
            || $tolerance === false || $tolerance < 0 || $frequency === false || $frequency < 1) {
            throw new DomainException('Tarifário inválido para ' . $type . '.');
        }

        return ['tipo_veiculo' => $type, 'valor_base' => str_replace(',', '.', $base),
            'valor_adicional' => str_replace(',', '.', $additional),
            'tolerancia_minutos' => (int) $tolerance, 'frequencia_adicional' => (int) $frequency];
    }

    private function companyForUpdate(PDO $connection, int $companyId): ?array
    {
        $statement = $connection->prepare(
            'SELECT id, name, slug, logo_path, deleted_at FROM companies WHERE id = :company_id FOR UPDATE'
        );
        $statement->execute(['company_id' => $companyId]);
        $company = $statement->fetch(PDO::FETCH_ASSOC);
        return $company === false ? null : $company;
    }

    private function assertSlugAvailable(PDO $connection, string $slug, int $companyId): void
    {
        $statement = $connection->prepare(
            'SELECT 1 FROM companies WHERE slug = :slug AND id <> :company_id LIMIT 1'
        );
        $statement->execute(['slug' => $slug, 'company_id' => $companyId]);
        if ($statement->fetchColumn() !== false) {
            throw new DomainException('Já existe uma empresa com esse slug.');
        }
    }

    private function audit(PDO $connection, int $companyId, array $data, array $company): void
    {
        $identity = IdentityContext::current();
        (new AuditLogRepository($connection))->insert([
            'user_id' => $identity->userId(), 'company_id' => $companyId,
            'actor_email' => $identity->email(), 'action' => 'UPDATE', 'entity' => 'companies',
            'entity_id' => (string) $companyId, 'old_values' => null,
            'new_values' => json_encode(['event' => 'COMPANY_PRICING_UPDATED',
                'legal_name' => $data['legal_name'], 'trade_name' => $data['trade_name'],
                'slug' => $data['slug'], 'logo_path' => $data['logo_path'] ?? $company['logo_path'],
                'billing_model' => $data['is_monthly'] ? 'monthly' : 'rotating',
                'tariffs' => $data['tariffs']], JSON_THROW_ON_ERROR),
            'ip_address' => $identity->ipAddress(), 'request_id' => $identity->requestId(),
            'created_at' => (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s.u'),
        ]);
    }

    private function storeLogo(?array $file): ?string
    {
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK
            || !is_uploaded_file((string) ($file['tmp_name'] ?? ''))
            || (int) ($file['size'] ?? 0) > 2 * 1024 * 1024) {
            throw new DomainException('Logo inválida ou maior que 2 MB.');
        }
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!isset($allowed[$mime])) {
            throw new DomainException('Formato inválido. Envie logo em JPG, PNG ou WEBP.');
        }
        $directory = dirname(__DIR__, 2) . '/public/uploads/companies';
        if ((!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory))
            || !is_writable($directory)) {
            throw new DomainException('Não foi possível preparar o diretório de logos.');
        }
        $name = sprintf('company_%s.%s', bin2hex(random_bytes(16)), $allowed[$mime]);
        if (!@move_uploaded_file($file['tmp_name'], $directory . DIRECTORY_SEPARATOR . $name)) {
            throw new DomainException('Não foi possível salvar a logo.');
        }
        return 'companies/' . $name;
    }

    private function assertGovernanceAdmin(): void
    {
        $roles = IdentityContext::current()->roleSlugs();
        if (!array_intersect(['master', 'super-admin', 'admin'], $roles)) {
            throw new ForbiddenException('admin.provision', 'Apenas usuários MASTER ou ADMIN podem provisionar acessos.');
        }
    }

    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function hasValidCsrf(): bool
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

    private function redirect(int $companyId): never
    {
        if ($companyId < 1) {
            $this->redirectCompanies();
        }
        header('Location: /admin/companies/company_id=' . $companyId);
        exit;
    }

    private function redirectCompanies(): never
    {
        header('Location: /admin/companies');
        exit;
    }
}

