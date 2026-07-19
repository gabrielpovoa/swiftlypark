<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\InvalidOtpException;
use App\Exceptions\InvalidResetAuthorizationException;
use App\Repositories\PasswordResetRepository;
use App\Repositories\UserRepository;
use App\Services\OtpService;
use App\Services\QueuedPasswordRecoveryMailer;
use Config\Database;
use Core\Controller;
use Throwable;

final class PasswordRecController extends Controller
{
    private const BLIND_RESPONSE =
        'Se o e-mail existir em nossa base de dados, um código de recuperação foi adicionado à fila de envio.';

    private OtpService $otpService;

    public function __construct(?OtpService $otpService = null)
    {
        parent::__construct();

        if ($otpService !== null) {
            $this->otpService = $otpService;

            return;
        }

        $connection = (new Database())->connect();
        $this->otpService = new OtpService(
            $connection,
            new PasswordResetRepository($connection),
            new UserRepository($connection),
            QueuedPasswordRecoveryMailer::fromConnection($connection)
        );
    }

    public function showForm(): void
    {
        $this->startSession();
        unset($_SESSION['password_reset_authorization']);
        $this->render('request');
    }

    public function requestOtp(): void
    {
        $this->startSession();

        if (!$this->hasValidCsrfToken()) {
            $this->render('request', ['error' => 'A sessão expirou. Tente novamente.']);

            return;
        }

        $email = filter_var(
            trim((string) ($_POST['email'] ?? '')),
            FILTER_VALIDATE_EMAIL
        );

        if ($email === false) {
            $this->render('request', ['error' => 'Digite um e-mail válido.']);

            return;
        }

        try {
            $this->otpService->requestOtp($email);
        } catch (Throwable $throwable) {
            error_log($throwable->getMessage());
        }

        $this->render('verify', [
            'email' => strtolower($email),
            'success' => self::BLIND_RESPONSE,
        ]);
    }

    public function verifyOtp(): void
    {
        $this->startSession();

        if (!$this->hasValidCsrfToken()) {
            $this->render('request', ['error' => 'A sessão expirou. Tente novamente.']);

            return;
        }

        $email = filter_var(
            trim((string) ($_POST['email'] ?? '')),
            FILTER_VALIDATE_EMAIL
        );
        $otp = trim((string) ($_POST['otp'] ?? ''));

        if ($email === false || preg_match('/^\d{5}$/', $otp) !== 1) {
            $this->render('verify', [
                'email' => (string) ($_POST['email'] ?? ''),
                'error' => 'Código inválido ou expirado.',
            ]);

            return;
        }

        try {
            $authorization = $this->otpService->validateOtp($email, $otp);
            session_regenerate_id(true);
            $_SESSION['password_reset_authorization'] = $authorization;
            $this->render('reset');
        } catch (InvalidOtpException $exception) {
            $this->render('verify', [
                'email' => strtolower($email),
                'error' => $exception->getMessage(),
            ]);
        } catch (Throwable $throwable) {
            error_log($throwable->getMessage());
            $this->render('verify', [
                'email' => strtolower($email),
                'error' => 'Não foi possível validar o código. Tente novamente.',
            ]);
        }
    }

    public function showResetForm(): void
    {
        $this->startSession();

        if (!$this->hasActiveAuthorization()) {
            $this->render('request', [
                'error' => 'Valide um novo código para redefinir sua senha.',
            ]);

            return;
        }

        $this->render('reset');
    }

    public function resetPassword(): void
    {
        $this->startSession();

        if (!$this->hasValidCsrfToken() || !$this->hasActiveAuthorization()) {
            unset($_SESSION['password_reset_authorization']);
            $this->render('request', [
                'error' => 'A autorização expirou. Solicite um novo código.',
            ]);

            return;
        }

        $password = (string) ($_POST['new_password'] ?? '');
        $confirmation = (string) ($_POST['confirm_password'] ?? '');

        if (strlen($password) < 12 || $password !== $confirmation) {
            $this->render('reset', [
                'error' => 'Use ao menos 12 caracteres e confirme a mesma senha.',
            ]);

            return;
        }

        $authorization = $_SESSION['password_reset_authorization'];

        try {
            $this->otpService->resetPassword(
                (int) $authorization['id'],
                (string) $authorization['token'],
                $password
            );
            unset($_SESSION['password_reset_authorization']);
            session_regenerate_id(true);
            $this->render('request', [
                'success' => 'Senha atualizada com sucesso. Você já pode entrar.',
            ]);
        } catch (InvalidResetAuthorizationException $exception) {
            unset($_SESSION['password_reset_authorization']);
            $this->render('request', ['error' => $exception->getMessage()]);
        } catch (Throwable $throwable) {
            error_log($throwable->getMessage());
            $this->render('reset', [
                'error' => 'Não foi possível atualizar a senha. Tente novamente.',
            ]);
        }
    }

    private function render(string $step, array $data = []): void
    {
        $data = array_merge([
            'title' => 'Recupere Sua Conta',
            'step' => $step,
            'csrfToken' => $this->csrfToken(),
        ], $data);

        $this->setView('Login/RecoveryPassword', $data, false);
    }

    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function csrfToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    private function hasValidCsrfToken(): bool
    {
        $submittedToken = (string) ($_POST['csrf_token'] ?? '');
        $sessionToken = (string) ($_SESSION['csrf_token'] ?? '');

        return $submittedToken !== ''
            && $sessionToken !== ''
            && hash_equals($sessionToken, $submittedToken);
    }

    private function hasActiveAuthorization(): bool
    {
        $authorization = $_SESSION['password_reset_authorization'] ?? null;

        return is_array($authorization)
            && isset(
                $authorization['id'],
                $authorization['token'],
                $authorization['expires_at']
            )
            && (int) $authorization['expires_at'] > time();
    }
}
