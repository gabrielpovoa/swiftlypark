<?php
    namespace App\Controllers;

    use Core\Controller;
    use App\Models\CreateVacancyModel;

    class CreateVacancy extends Controller
    {
        public function index()
        {
            $this->setView('Vacancy/CreateVacancy', [
                'title' => 'Adicionar Novas Vagas'
            ]);
        }

        public function store()
        {
            $category = $_POST['category'] ?? '';
            $amount = (int) ($_POST['amount'] ?? 0);

            $model = new CreateVacancyModel();

            if ($category && $amount > 0) {
                $success = $model->createVacancy($category, $amount);
                $successMessage = $success ? "$amount vaga(s) de '$category' criadas com sucesso!" : '';
            } else {
                $success = false;
                $errorMessage = 'Preencha todos os campos corretamente.';
            }

            $this->setView('Vacancy/CreateVacancy', [
                'title' => 'Adicionar Novas Vagas',
                'successMessage' => $success ? $successMessage : '',
                'errorMessage' => !$success ? ($errorMessage ?? 'Erro ao criar vagas.') : ''
            ]);
        }
    }
