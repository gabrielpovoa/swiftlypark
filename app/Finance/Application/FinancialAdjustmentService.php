<?php

declare(strict_types=1);

namespace App\Finance\Application;

use App\Context\RequestIdentity;
use App\Finance\Infrastructure\FinancialAdjustmentRepository;
use App\Finance\Domain\AdjustmentType;
use App\Services\AuthorizationService;
use App\Transactions\TransactionManager;
use DomainException;
use App\Security\InputSanitizer;

final class FinancialAdjustmentService
{
    public function __construct(
        private FinancialAdjustmentRepository $adjustments,
        private FinancialAuditService $audit,
        private TransactionManager $transactions,
        private RequestIdentity $identity
    ) {
    }

    public function refund(
        int $transactionId,
        float $amount,
        string $reason
    ): int {
        (new AuthorizationService($this->identity))->check('finance.adjust');
        $reason = (new InputSanitizer())->text($reason, 1000);

        if ($transactionId < 1 || $amount <= 0 || strlen($reason) < 10) {
            throw new DomainException(
                'Informe uma transação, um valor válido e uma justificativa detalhada.'
            );
        }

        return $this->transactions->run(function () use (
            $transactionId,
            $amount,
            $reason
        ): int {
            $transaction = $this->adjustments->findTransactionForUpdate(
                $transactionId
            );

            if ($transaction === null) {
                throw new DomainException('Transação não encontrada.');
            }

            if (
                $this->adjustments->totalAdjusted($transactionId) + $amount
                > (float) $transaction['valor']
            ) {
                throw new DomainException(
                    'O total dos ajustes não pode exceder o pagamento original.'
                );
            }

            $id = $this->adjustments->create(
                $transactionId,
                AdjustmentType::Refund->value,
                $amount,
                $reason,
                $this->identity->userId()
            );
            $this->audit->recordAdjustment(
                $id,
                $transactionId,
                AdjustmentType::Refund->value,
                $amount,
                $reason
            );

            return $id;
        });
    }
}
