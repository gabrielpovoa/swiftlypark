<?php

    namespace App\controllers;

    use Core\Controller;

    class CreateAccController extends Controller
    {
        public function index()
        {
            $this->setView('CreateAcc/createacc', [
                'title' => 'Crie sua Conta - SwiftlyPark'
            ],false);
        }

    }