<?php

    namespace App\Controllers;

    use Core\Controller;
    use App\Models\CreateAccModel;

    class CreateAccController extends Controller
    {
        public function index()
        {
            $this->setView('CreateAcc/createacc', [
                'title' => 'Crie sua Conta - SwiftlyPark'
            ], false);
        }

        public function createAcc()
        {
            $model = new CreateAccModel(); // 🔹 Cria o model

            $email = $_POST['email'] ?? '';

            if ($model->verifyUser($email)) {
                echo "Este e-mail já está cadastrado.";
                return; // Interrompe a criação
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $name     = trim($_POST['name'] ?? '');
                $email    = trim($_POST['email'] ?? '');
                $password = trim($_POST['password'] ?? '');

                // Validações simples
                if (empty($name) || empty($email) || empty($password)) {
                    $this->setView('CreateAcc/createacc', [
                        'title' => 'Crie sua Conta - SwiftlyPark',
                        'error' => 'Todos os campos são obrigatórios.'
                    ], false);
                    return;
                }

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $this->setView('CreateAcc/createacc', [
                        'title' => 'Crie sua Conta - SwiftlyPark',
                        'error' => 'Email inválido.'
                    ], false);
                    return;
                }

                try {
                    $userId = $model->createUser($name, $email, $password);

                    if ($userId) {
                        header("Location: /");
                        exit;
                    } else {
                        throw new \Exception("Erro ao criar conta.");
                    }
                } catch (\Exception $e) {
                    $this->setView('CreateAcc/createacc', [
                        'title' => 'Crie sua Conta - SwiftlyPark',
                        'error' => $e->getMessage()
                    ], false);
                }
            }
        }

    }
