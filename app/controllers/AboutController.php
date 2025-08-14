<?php

    namespace App\controllers;

    use Core\Controller;

    class AboutController extends Controller
    {
        public function index()
        {
            $this->setView('About/about', [
                'title' => 'Sobre o Projeto - SwiftlyPark',
            ]);
        }
    }