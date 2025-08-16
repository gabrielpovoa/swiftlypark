<?php
namespace App\Controllers;

use Core\Controller;
use App\Models\HomeDashModel;

class HomeController extends Controller {
    public function index()
    {
        $model = new HomeDashModel();
        $dailyIncome = $model->getDailyIncome();
        $logEntry = $model->getLogEntry();

        $this->setView('HomeDash/homedash', [
            'title' => 'Dashboard SwiftlyPark',
            'dailyIncome' => $dailyIncome,
            'logEntry' => $logEntry,
            ]);
    }
}
