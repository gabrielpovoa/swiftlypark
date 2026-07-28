<?php
    namespace App\Controllers;

    use Core\Controller;
    use App\Models\HomeDashModel;
    use App\Context\IdentityContext;
    use App\Services\AuthorizationService;
    use App\Companies\Infrastructure\PdoCompanyVehicleTypeRepository;
    use App\Context\TenantContext;
    use Config\Database;

    class HomeController extends Controller {
        public function index()
        {
            $identity = IdentityContext::current();

            $localTimezone = new \DateTimeZone('America/Sao_Paulo');
            $utcTimezone = new \DateTimeZone('UTC');
            $monthStart = new \DateTimeImmutable('first day of this month 00:00:00', $localTimezone);
            $nextMonthStart = $monthStart->modify('first day of next month');

            $model = new HomeDashModel();
            $monthlyIncome = $model->getIncomeByPeriod(
                $monthStart->setTimezone($utcTimezone)->format('Y-m-d H:i:s'),
                $nextMonthStart->setTimezone($utcTimezone)->format('Y-m-d H:i:s')
            );
            $logEntry = $model->getLogEntriesByPeriod(
                $monthStart->format('Y-m-d H:i:s'),
                $nextMonthStart->format('Y-m-d H:i:s')
            );
            $authorization = new AuthorizationService($identity);

            $this->setView('HomeDash/homedash', [
                'title' => 'Dashboard SwiftlyPark',
                'monthlyIncome' => $monthlyIncome,
                'logEntry' => $logEntry,
                'enabledVehicleTypes' => (new PdoCompanyVehicleTypeRepository(
                    (new Database())->connect()
                ))->enabledTypes((int) TenantContext::instance()->getCompanyId()),
                'canCheckin' => $authorization->can('vehicle.checkin'),
                'canViewReports' => $authorization->can('report.view'),
            ]);
        }
    }
