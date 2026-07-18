SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE parking_active_stays (
    company_id BIGINT UNSIGNED NOT NULL,
    vacancy_id INT NOT NULL,
    filled_vacancy_id INT NOT NULL,
    acquired_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (company_id, vacancy_id),
    UNIQUE KEY uq_parking_active_filled (company_id, filled_vacancy_id),
    CONSTRAINT fk_parking_active_company FOREIGN KEY (company_id) REFERENCES companies (id)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_parking_active_vacancy FOREIGN KEY (vacancy_id) REFERENCES vagas_disponiveis (id_vaga)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_parking_active_filled FOREIGN KEY (filled_vacancy_id)
        REFERENCES vagas_preenchidas (id_vaga_preenchida)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

INSERT INTO parking_active_stays (company_id, vacancy_id, filled_vacancy_id, acquired_at)
SELECT company_id, id_vaga, id_vaga_preenchida, hora_entrada
FROM vagas_preenchidas
WHERE hora_saida IS NULL;
