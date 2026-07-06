<?php
    namespace App\Controllers;

    use App\Context\IdentityContext;
    use Core\Controller;
    use App\Models\CreateVacancyModel;
    use App\Services\AuthorizationService;

    class CreateVacancy extends Controller
    {
        public function index()
        {
            $this->authorize();

            $this->setView('Vacancy/CreateVacancy', [
                'title' => 'Adicionar Novas Vagas'
            ]);
        }

        public function store()
        {
            $this->authorize();

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

        private function authorize(): void
        {
            (new AuthorizationService(IdentityContext::current()))
                ->check('vacancy.create');
        }
    }
