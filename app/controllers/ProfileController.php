<?php


    namespace App\controllers;

    use Core\Controller;

    class ProfileController extends Controller
    {
        public function index()
        {
            $this->setView('Profile/profile', [
                'title' => 'Perfil - SwiftlyPark'
            ]);
        }

    }