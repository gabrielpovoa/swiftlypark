<?php
    namespace App\Controllers;

    use Core\Controller;
    use App\Models\HomeDashModel;
    use App\Context\IdentityContext;
    use App\Services\AuthorizationService;

    class HomeController extends Controller {
        public function index()
        {
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
            $authorization = new AuthorizationService(
                IdentityContext::current()
            );

            $this->setView('HomeDash/homedash', [
                'title' => 'Dashboard SwiftlyPark',
                'monthlyIncome' => $monthlyIncome,
                'logEntry' => $logEntry,
                'canCheckin' => $authorization->can('vehicle.checkin'),
                'canViewReports' => $authorization->can('report.view'),
            ]);
        }
    }
