<?php
    namespace App\Models;

    use Config\Database;
    use PDO;

    class CreateVacancyModel
    {
        private $db;

        public function __construct()
        {
            $this->db = (new Database())->connect();
        }

        /**
         * Cria novas vagas de acordo com a categoria e quantidade
         */
        public function createVacancy(string $category, int $amount): bool
        {
            try {
                $this->db->beginTransaction();

                $stmt = $this->db->prepare("INSERT INTO vagas_disponiveis (categoria) VALUES (:categoria)");

                for ($i = 0; $i < $amount; $i++) {
                    $stmt->execute([':categoria' => $category]);
                }

                $this->db->commit();
                return true;

            } catch (\PDOException $e) {
                $this->db->rollBack();
                return false;
            }
        }
    }
