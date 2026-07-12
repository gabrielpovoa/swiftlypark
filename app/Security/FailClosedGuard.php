<?php

declare(strict_types=1);

namespace App\Security;

use App\Exceptions\SecurityCriticalException;

final class FailClosedGuard
{
    public const SCOPE_OPERATIONAL = 'operational';
    public const SCOPE_SYSTEM = 'system';
    public const SCOPE_GLOBAL = 'global';

    public function assertOperationalQueryIsTenantScoped(
        string $query,
        array $parameters,
        ?int $activeCompanyId
    ): void {
        if ($activeCompanyId === null) {
            throw new SecurityCriticalException(
                SecurityCriticalException::CODE_TENANT_CONTEXT_MISSING,
                'Consulta operacional bloqueada porque não existe TenantContext ativo.'
            );
        }

        $normalizedQuery = $this->normalizeSql($query);
        if (!preg_match('/\b[a-z0-9_]*company_id\b/i', $normalizedQuery)) {
            throw new SecurityCriticalException(
                SecurityCriticalException::CODE_QUERY_WITHOUT_TENANT_SCOPE,
                'Consulta operacional bloqueada porque não contém filtro por company_id.'
            );
        }

        $tenantParameters = array_filter(
            $parameters,
            static fn (string|int $key): bool => is_string($key)
                && str_contains(strtolower($key), 'company_id'),
            ARRAY_FILTER_USE_KEY
        );

        if ($tenantParameters === []) {
            throw new SecurityCriticalException(
                SecurityCriticalException::CODE_QUERY_TENANT_PARAMETER_MISSING,
                'Consulta operacional bloqueada porque não vincula company_id aos parâmetros.'
            );
        }

        foreach ($tenantParameters as $value) {
            if ((int) $value === $activeCompanyId) {
                return;
            }
        }

        throw new SecurityCriticalException(
            SecurityCriticalException::CODE_CROSS_TENANT_ACCESS,
            'Consulta operacional bloqueada porque o company_id solicitado difere do tenant ativo.'
        );
    }

    public function assertKnownScope(string $scope): void
    {
        if (!in_array($scope, [
            self::SCOPE_OPERATIONAL,
            self::SCOPE_SYSTEM,
            self::SCOPE_GLOBAL,
        ], true)) {
            throw new SecurityCriticalException(
                SecurityCriticalException::CODE_QUERY_WITHOUT_TENANT_SCOPE,
                'Escopo de consulta desconhecido.'
            );
        }
    }

    private function normalizeSql(string $query): string
    {
        $query = preg_replace('/--.*$/m', ' ', $query) ?? $query;
        $query = preg_replace('#/\*.*?\*/#s', ' ', $query) ?? $query;
        $query = preg_replace("/'([^'\\\\]|\\\\.)*'/", "''", $query) ?? $query;

        return preg_replace('/\s+/', ' ', trim($query)) ?? trim($query);
    }
}
