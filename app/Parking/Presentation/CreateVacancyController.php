<?php
    namespace App\Parking\Presentation;

    use App\Context\IdentityContext;
    use Core\Controller;
    use App\Parking\Application\CreateVacancies;
    use App\Parking\Infrastructure\PdoVacancyCreator;
    use App\Services\AuthorizationService;

    final class CreateVacancyController extends Controller
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

            $model = new CreateVacancies(new PdoVacancyCreator());

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
